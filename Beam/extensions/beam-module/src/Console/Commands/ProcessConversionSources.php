<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;
use Remp\Journal\ListRequest;

class ProcessConversionSources extends Command
{
    const COMMAND = 'conversions:process-sources';

    private $journal;

    protected $signature = self::COMMAND . ' {--conversion_id=}';

    protected $description = 'Retrieve visit sources that lead to conversion';

    public function __construct(JournalContract $journal)
    {
        parent::__construct();

        $this->journal = $journal;
    }

    public function handle()
    {
        $this->line('Started processing of conversion sources');
        $conversionId = $this->option('conversion_id') ?? null;

        try {
            if ($conversionId) {
                $conversion = Conversion::find($conversionId);
                if (!$conversion) {
                    $this->error("Conversion with ID $conversionId not found.");
                    return 1;
                }

                if (!$conversion->events_aggregated) {
                    $this->warn("Conversion with ID $conversionId needs to be aggregated prior to running of this command.");
                    return 1;
                }

                if ($conversion->source_processed) {
                    $this->info("Sources for conversion with ID $conversionId has already been processed.");
                    return 1;
                }

                $this->processConversionSources($conversion);
                $conversion->source_processed = true;
                $conversion->save();
            } else {
                $conversionsToProcess = Conversion::select()
                    ->where('events_aggregated', true)
                    ->where('source_processed', false)
                    ->orderBy('paid_at', 'DESC')
                    ->get();

                foreach ($conversionsToProcess as $conversion) {
                    $this->processConversionSources($conversion);
                    $conversion->source_processed = true;
                    $conversion->save();
                }
            }
        } catch (JournalException $exception) {
            $this->error($exception->getMessage());
        }

        $this->line(' <info>Done!</info>');
        return 0;
    }

    private function processConversionSources(Conversion $conversion)
    {
        $this->line("Processing sources for conversion <info>#{$conversion->id}</info>");

        /** @var ConversionCommerceEvent $paymentEvent */
        $paymentEvent = $conversion->commerceEvents()
            ->where('step', 'payment')
            ->latest('time')
            ->first();

        if (!$paymentEvent) {
            Log::warning("No payment event found in DB for conversion: {$conversion->id}");
            $this->warn("No payment event found in DB for conversion with ID $conversion->id, skipping...");
            return;
        }

        if (!$browserId = $this->getConversionBrowserId($conversion)) {
            return;
        }

        // with payment event and browser ID we can determine session and find first/last pageview of session

        $from = (clone $paymentEvent->time)->subDay();
        $to = (clone $paymentEvent->time);

        $pageviewsListRequest = ListRequest::from('pageviews')
            ->setTime($from, $to)
            ->addFilter('browser_id', $browserId);

        $pageviewJournalEvents = $this->journal->list($pageviewsListRequest);
        if (empty($pageviewJournalEvents)) {
            $this->warn("No pageview found in journal for conversion with ID $paymentEvent->conversion_id, skipping...");
            return;
        }

        $pageviews = collect($pageviewJournalEvents[0]->pageviews);
        $pageviews = $pageviews->sortByDesc(function ($item) {
            return new Carbon($item->system->time);
        });

        $lastSessionPageview = $pageviews->first();
        $firstSessionPageview = null;
        foreach ($pageviews as $pageview) {
            if ($pageview->user->remp_session_id === $lastSessionPageview->user->remp_session_id) {
                $firstSessionPageview = $pageview;
            }
        }

        $this->createConversionSourceModel($firstSessionPageview, $conversion, ConversionSource::TYPE_SESSION_FIRST);
        $this->createConversionSourceModel($lastSessionPageview, $conversion, ConversionSource::TYPE_SESSION_LAST);
    }

    private function getConversionBrowserId(Conversion $conversion)
    {
        $paymentListRequest = ListRequest::from('commerce')
            ->addFilter('transaction_id', $conversion->transaction_id)
            ->addFilter('step', 'payment')
            ->addGroup('browser_id');

        $paymentJournalEvent = $this->journal->list($paymentListRequest);
        if (empty($paymentJournalEvent)) {
            $this->warn("No payment event found in journal for conversion with ID $conversion->id, skipping...");
            return false;
        }
        if (empty($paymentJournalEvent[0]->tags->browser_id)) {
            Log::warning("No browser_id available in commerce event for transaction_id: {$conversion->transaction_id}");
            $this->warn("No identifiable browser found in journal for conversion with ID $conversion->id, skipping...");
            return false;
        }

        return $paymentJournalEvent[0]->tags->browser_id;
    }

    private function createConversionSourceModel($pageview, Conversion $conversion, string $type)
    {
        $conversionSource = new ConversionSource();

        $conversionSource->type = $type;
        $conversionSource->referer_medium = $pageview->user->derived_referer_medium;
        if (!empty($pageview->user->derived_referer_source)) {
            $conversionSource->referer_source = $pageview->user->derived_referer_source;
        }
        if (!empty($pageview->user->derived_referer_host_with_path)) {
            $conversionSource->referer_host_with_path = $pageview->user->derived_referer_host_with_path;
        }
        if (isset($pageview->article->id)) {
            $article = Article::where('external_id', $pageview->article->id)->first();
            if ($article) {
                $conversionSource->article_id = $article->id;
            }
        }

        $conversionSource->conversion()->associate($conversion);
        $conversionSource->save();
    }
}
