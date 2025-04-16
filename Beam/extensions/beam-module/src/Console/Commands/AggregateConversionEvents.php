<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Helpers\Journal\JournalHelpers;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionCommerceEventProduct;
use Remp\BeamModule\Model\ConversionGeneralEvent;
use Remp\BeamModule\Model\ConversionPageviewEvent;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;
use Illuminate\Support\Collection;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\JournalException;
use Remp\Journal\ListRequest;

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
        $this->line('Aggregation of conversion events started');
        $conversionId = $this->option('conversion_id') ?? null;

        $days = (int) $this->option('days');

        if ($conversionId) {
            $conversion = Conversion::find($conversionId);
            if (!$conversion) {
                $this->error("Conversion with ID $conversionId not found.");
                return 1;
            }

            if ($conversion->events_aggregated) {
                $this->info("Conversion with ID $conversionId already aggregated.");
                return 2;
            }

            $this->aggregateConversion($conversion, $days);
        } else {
            foreach ($this->getUnaggregatedConversions() as $conversion) {
                $this->aggregateConversion($conversion, $days);
            }
        }

        $this->line(' <info>Done!</info>');
        return 0;
    }

    protected function getBrowsersForUser(Conversion $conversion, $days, $category, $action = null)
    {
        $before = $conversion->paid_at;
        // take maximum one year old browser IDs
        $after = (clone $before)->subDays($days);

        $browserIds = [];
        $records = $this->journal->count(AggregateRequest::from($category, $action)
            ->addGroup('browser_id')
            ->addFilter('user_id', $conversion->user_id)
            ->setTime($after, $before));

        foreach ($records as $record) {
            if (!$record->count) {
                continue;
            }
            if ($record->tags->browser_id && $record->tags->browser_id !== '') {
                $browserIds[] = $record->tags->browser_id;
            }
        }

        return $browserIds;
    }

    private function getUnaggregatedConversions(): Collection
    {
        return Conversion::where('events_aggregated', 0)
            ->orderBy('paid_at', 'DESC')
            ->get();
    }

    protected function aggregateConversion(Conversion $conversion, int $days)
    {
        if (!$conversion->user_id) {
            $this->line("Conversion #{$conversion->id} has no assigned user.");
            return;
        }

        $this->line("Aggregating conversion <info>#{$conversion->id}</info>");

        $userBrowserIds = array_unique(
            array_merge(
                $this->getBrowsersForUser($conversion, $days, 'pageviews', 'load'),
                $this->getBrowsersForUser($conversion, $days, 'commerce'),
                $this->getBrowsersForUser($conversion, $days, 'events'),
            )
        );

        try {
            $pageviewEvents = $this->loadPageviewEvents(
                $conversion,
                $userBrowserIds,
                $days
            );

            $commerceEvents = $this->loadCommerceEvents(
                $conversion,
                $userBrowserIds,
                $days
            );
            $generalEvents = $this->loadGeneralEvents(
                $conversion,
                $userBrowserIds,
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

        $r = ListRequest::from('pageviews')
            ->setTime($from, $to)
            ->addFilter('browser_id', ...$browserIds)
            ->setLoadTimespent();

        $events = collect($this->journal->list($r));

        $toSave = [];

        if ($events->isNotEmpty()) {
            foreach ($events[0]->pageviews as $item) {
                if (!isset($item->article->id)) {
                    continue;
                }
                $article = Article::where('external_id', $item->article->id)->first();

                if ($article) {
                    $time = Carbon::parse($item->system->time);
                    $timeToConversion = $time->diffInMinutes($conversion->paid_at);

                    $toSave[] = [
                        'conversion_id' => $conversion->id,
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'article_id' => $article->id,
                        'locked' => isset($item->article->locked) ? filter_var($item->article->locked, FILTER_VALIDATE_BOOLEAN) : null,
                        'signed_in' => isset($item->user->id),
                        'timespent' => $item->user->timespent ?? null,
                        // Background compatibility with utm_ -- will be removed
                        'rtm_campaign' => $item->user->source->rtm_campaign ?? $item->user->source->utm_campaign ?? null,
                        'rtm_content' => $item->user->source->rtm_content ?? $item->user->source->utm_content ?? null,
                        'rtm_medium' => $item->user->source->rtm_medium ?? $item->user->source->utm_medium ?? null,
                        'rtm_source' => $item->user->source->rtm_source ?? $item->user->source->utm_source ?? null,
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
            $events = collect($this->journal->list($request));
            if ($events->isNotEmpty()) {
                foreach ($events[0]->commerces as $item) {
                    if (array_key_exists($item->id, $processedIds)) {
                        continue;
                    }

                    $processedIds[$item->id] = true;

                    $time = Carbon::parse($item->system->time);
                    $timeToConversion = $time->diffInMinutes($conversion->paid_at);

                    $step = $item->step;

                    $toSave[] = [
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'step' => $step,
                        'funnel_id' => $item->$step->funnel_id ?? null,
                        'amount' => $item->$step->revenue->amount ?? null,
                        'currency' => $item->$step->revenue->currency ?? null,
                        // background compatibility with utm -- will be removed
                        'rtm_campaign' => $item->source->rtm_campaign ?? $item->source->utm_campaign ?? null,
                        'rtm_content' => $item->source->rtm_content ?? $item->source->utm_content ?? null,
                        'rtm_medium' => $item->source->rtm_medium ?? $item->source->utm_medium ?? null,
                        'rtm_source' => $item->source->rtm_source ?? $item->source->utm_source ?? null,
                        'conversion_id' => $conversion->id,
                        self::TEMP_PRODUCT_IDS_LABEL => $item->$step->product_ids ?? [] // this will be removed later
                    ];
                }
            }
        };

        if ($browserIds) {
            $process(ListRequest::from("commerce")
                ->setTime($from, $to)
                ->addFilter('browser_id', ...$browserIds));
        }

        $process(ListRequest::from("commerce")
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
            $events = collect($this->journal->list($request));
            if ($events->isNotEmpty()) {
                foreach ($events[0]->events as $item) {
                    if (array_key_exists($item->id, $processedIds)) {
                        continue;
                    }

                    $processedIds[$item->id] = true;

                    $time = Carbon::parse($item->system->time);
                    $timeToConversion = $time->diffInMinutes($conversion->paid_at);

                    $toSave[] = [
                        'time' => $time,
                        'minutes_to_conversion' => $timeToConversion,
                        'action' => $item->action ?? null,
                        'category' => $item->category ?? null,
                        'conversion_id' => $conversion->id,
                        // Background compatibility with utm_ -- will be removed
                        'rtm_campaign' => $item->rtm_campaign ?? $item->utm_campaign ?? null,
                        'rtm_content' => $item->rtm_content ?? $item->utm_content ?? null,
                        'rtm_medium' => $item->rtm_medium ?? $item->utm_medium ?? null,
                        'rtm_source' => $item->rtm_source ?? $item->utm_source ?? null,
                    ];
                }
            }
        };

        if ($browserIds) {
            $process(ListRequest::from('events')
                ->setTime($from, $to)
                ->addFilter('browser_id', ...$browserIds));
        }

        $process(ListRequest::from('events')
            ->setTime($from, $to)
            ->addFilter('user_id', $conversion->user_id));

        return $toSave;
    }
}
