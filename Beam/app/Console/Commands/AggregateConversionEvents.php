<?php

namespace App\Console\Commands;

use App\Article;
use App\Contracts\JournalAggregateRequest;
use App\Contracts\JournalContract;
use App\Contracts\JournalException;
use App\Contracts\JournalHelpers;
use App\Contracts\JournalListRequest;
use App\Conversion;
use App\Model\ConversionCommerceEvent;
use App\Model\ConversionCommerceEventProduct;
use App\Model\ConversionGeneralEvent;
use App\Model\ConversionPageviewEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AggregateConversionEvents extends Command
{
    const COMMAND = 'conversions:aggregate-events';
    const DAYS_IN_PAST = 14;
    private const TEMP_PRODUCT_IDS_LABEL = 'TEMP_product_ids';

    protected $signature = self::COMMAND . ' {--conversion_id=} {--days=' . self::DAYS_IN_PAST . '}';

    protected $description = 'Aggregate events prior given conversion';

    private $journal;

    private $journalHelper;

    public function __construct(JournalContract $journal)
    {
        parent::__construct();
        $this->journal = $journal;
        $this->journalHelper = new JournalHelpers($journal);
    }

    public function handle()
    {
        ini_set('memory_limit', '1G');
        $this->line('Aggregation of conversion events started');
        $conversionId = $this->option('conversion_id') ?? null;

        $days = (int) $this->option('days');

        if ($conversionId) {
            $conversion = Conversion::find($conversionId);
            if (!$conversion) {
                $this->error("Conversion with ID $conversionId not found.");
                return;
            }

            if ($conversion->events_aggregated) {
                $this->info("Conversion with ID $conversionId already aggregated.");
                return;
            }

            $this->aggregateConversion($conversion, $days);
        } else {
            foreach ($this->getUnaggregatedConversions() as $conversion) {
                $this->aggregateConversion($conversion, $days);
            }
        }

        $this->line(' <info>Done!</info>');
    }

    protected function getBrowsersForUser(Conversion $conversion, $category, $action = null)
    {
        $before = $conversion->paid_at;
        // take maximum one year old browser IDs
        $after = (clone $before)->subYear();

        $browserIds = [];
        $records = $this->journal->count(JournalAggregateRequest::from($category, $action)
            ->addGroup('browser_id')
            ->addFilter('user_id', $conversion->user_id)
            ->setTime($after, $before));
        foreach ($records as $record) {
            if ($record->tags->browser_id && $record->tags->browser_id !== '') {
                $browserIds[] = $record->tags->browser_id;
            }
        }

        return $browserIds;
    }

    private function getUnaggregatedConversions(): Collection
    {
        return Conversion::where('events_aggregated', 0)->get();
    }

    protected function aggregateConversion(Conversion $conversion, int $days)
    {
        if (!$conversion->user_id) {
            $this->line("Conversion #{$conversion->id} has no assigned user.");
            return;
        }

        $this->line("Aggregating conversion <info>#{$conversion->id}</info>");

        try {
            $pageviewEvents = $this->loadPageviewEvents(
                $conversion,
                $this->getBrowsersForUser($conversion, 'pageviews', 'load'),
                $days
            );

            $commerceEvents = $this->loadCommerceEvents(
                $conversion,
                $this->getBrowsersForUser($conversion, 'commerce'),
                $days
            );
            $generalEvents = $this->loadGeneralEvents(
                $conversion,
                $this->getBrowsersForUser($conversion, 'events'),
                $days
            );

            // Compute event numbers prior conversion (across all event types)
            $times = [];
            foreach ($pageviewEvents as $event) {
                $times[] = $event['time']->timestamp;
            }
            foreach ($commerceEvents as $event) {
                $times[] = $event['time']->timestamp;
            }
            foreach ($generalEvents as $event) {
                $times[] = $event['time']->timestamp;
            }
            rsort($times);
            $timesIndex = [];

            foreach ($times as $i => $timestamp) {
                $timesIndex[(string)$timestamp] = $i + 1;
            }

            // Save events
            $this->savePageviewEvents($pageviewEvents, $timesIndex);
            $this->saveCommerceEvents($commerceEvents, $timesIndex);
            $this->saveGeneralEvents($generalEvents, $timesIndex);

            $conversion->events_aggregated = true;
            $conversion->save();
        } catch (JournalException $exception) {
            $this->error($exception->getMessage());
        }
    }

    protected function savePageviewEvents(array &$events, &$timesIndex)
    {
        foreach ($events as $data) {
            $data['event_prior_conversion'] = $timesIndex[(string)$data['time']->timestamp];
            ConversionPageviewEvent::create($data);
        }
    }

    protected function saveCommerceEvents(array &$events, &$timesIndex)
    {
        foreach ($events as $data) {
            $data['event_prior_conversion'] = $timesIndex[(string)$data['time']->timestamp];
            $productIds = $data[self::TEMP_PRODUCT_IDS_LABEL];
            unset($data[self::TEMP_PRODUCT_IDS_LABEL]);

            $commerceEvent = ConversionCommerceEvent::create($data);
            foreach ($productIds as $productId) {
                $product = new ConversionCommerceEventProduct(['product_id' => $productId]);
                $commerceEvent->products()->save($product);
            }
        }
    }

    protected function saveGeneralEvents(array &$events, &$timesIndex)
    {
        foreach ($events as $data) {
            $data['event_prior_conversion'] = $timesIndex[(string)$data['time']->timestamp];
            ConversionGeneralEvent::create($data);
        }
    }

    protected function loadPageviewEvents(Conversion $conversion, array $browserIds, $days): array
    {
        if (!$browserIds) {
            return [];
        }

        $from = (clone $conversion->paid_at)->subDays($days);
        $to = $conversion->paid_at;

        $r = JournalListRequest::from('pageviews')
            ->setTime($from, $to)
            ->addFilter('browser_id', ...$browserIds)
            ->setLoadTimespent();

        $events = $this->journal->list($r);

        $toSave = [];

        if ($events->isNotEmpty()) {
            foreach ($events[0]->pageviews as $item) {
                if (!isset($item->article->id)) {
                    continue;
                }
                $article = Article::where('external_id', $item->article->id)->first();

                if ($article) {
                    $time = Carbon::parse($item->system->time)->tz('UTC');
                    $timeToConversion = $conversion->paid_at->diffInMinutes($time);

                    $toSave[] = [
                        'conversion_id' => $conversion->id,
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'article_id' => $article->id,
                        'locked' => isset($item->article->locked) ? filter_var($item->article->locked, FILTER_VALIDATE_BOOLEAN) : null,
                        'signed_in' => isset($item->user->id),
                        'timespent' => $item->user->timespent ?? null,
                        'utm_campaign' => $item->user->source->utm_campaign ?? null,
                        'utm_content' => $item->user->source->utm_content ?? null,
                        'utm_medium' => $item->user->source->utm_medium ?? null,
                        'utm_source' => $item->user->source->utm_source ?? null,
                    ];
                }
            }
        }

        return $toSave;
    }

    protected function loadCommerceEvents(Conversion $conversion, array $browserIds, $days): array
    {
        $from = (clone $conversion->paid_at)->subDays($days);
        $to = $conversion->paid_at;

        $processedIds = [];

        $toSave = [];

        $process = function ($request) use ($conversion, &$processedIds, &$toSave) {
            $events = $this->journal->list($request);
            if ($events->isNotEmpty()) {
                foreach ($events[0]->commerces as $item) {
                    if (array_key_exists($item->id, $processedIds)) {
                        continue;
                    }

                    $processedIds[$item->id] = true;

                    $time = Carbon::parse($item->system->time)->tz('UTC');
                    $timeToConversion = $conversion->paid_at->diffInMinutes($time);

                    $step = $item->step;

                    $toSave[] = [
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'step' => $step,
                        'funnel_id' => $item->$step->funnel_id ?? null,
                        'amount' => $item->$step->revenue->amount ?? null,
                        'currency' => $item->$step->revenue->currency ?? null,
                        'utm_campaign' => $item->source->utm_campaign ?? null,
                        'utm_content' => $item->source->utm_content ?? null,
                        'utm_medium' => $item->source->utm_medium ?? null,
                        'utm_source' => $item->source->utm_source ?? null,
                        'conversion_id' => $conversion->id,
                        self::TEMP_PRODUCT_IDS_LABEL => $item->$step->product_ids ?? [] // this will be removed later
                    ];
                }
            }
        };

        if ($browserIds) {
            $process(JournalListRequest::from("commerce")
                ->setTime($from, $to)
                ->addFilter('browser_id', ...$browserIds));
        }

        $process(JournalListRequest::from("commerce")
            ->setTime($from, $to)
            ->addFilter('user_id', $conversion->user_id));

        return $toSave;
    }

    protected function loadGeneralEvents(Conversion $conversion, array $browserIds, $days): array
    {
        $from = (clone $conversion->paid_at)->subDays($days);
        $to = $conversion->paid_at;

        $processedIds = [];

        $toSave = [];

        $process = function ($request) use ($conversion, &$processedIds, &$toSave) {
            $events = $this->journal->list($request);
            if ($events->isNotEmpty()) {
                foreach ($events[0]->events as $item) {
                    if (array_key_exists($item->id, $processedIds)) {
                        continue;
                    }

                    $processedIds[$item->id] = true;

                    $time = Carbon::parse($item->system->time)->tz('UTC');
                    $timeToConversion = $conversion->paid_at->diffInMinutes($time);

                    $toSave[] = [
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'action' => $item->action ?? null,
                        'category' => $item->category ?? null,
                        'conversion_id' => $conversion->id,
                        'utm_campaign' => $item->utm_campaign ?? null,
                        'utm_content' => $item->utm_content ?? null,
                        'utm_medium' => $item->utm_medium ?? null,
                        'utm_source' => $item->utm_source ?? null,
                    ];
                }
            }
        };

        if ($browserIds) {
            $process(JournalListRequest::from('events')
                ->setTime($from, $to)
                ->addFilter('browser_id', ...$browserIds));
        }

        $process(JournalListRequest::from('events')
            ->setTime($from, $to)
            ->addFilter('user_id', $conversion->user_id));

        return $toSave;
    }
}
