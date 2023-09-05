<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Console\Command;
use Illuminate\Support\Carbon;
use Remp\Journal\AggregateRequest;
use Remp\Journal\JournalContract;

class ProcessPageviewLoyalVisitors extends Command
{
    protected $signature = 'pageviews:loyal-visitors {--days=}';

    protected $description = 'Determines number of articles read by top 10% of readers and creates segment based on it';

    public function handle(JournalContract $journalContract)
    {
        $days = $this->option('days') ?? 30;

        if (!$days) {
            $this->line("No days to process, exiting.");
            return 0;
        }

        $bar = $this->output->createProgressBar($days);
        $bar->setFormat('%message%: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->setMessage('Extracting aggregated pageview data (days)');

        $browsersToUsers = [];
        $readers = [];

        foreach (range(1, $days) as $dayOffset) {
            $now = Carbon::now();
            $timeBefore = $now->copy()->setTime(0, 0)->subDays($dayOffset);
            $timeAfter = $now->copy()->setTime(0, 0)->subDays($dayOffset+1);

            $request = new AggregateRequest('pageviews', 'load');
            $request->setTimeAfter($timeAfter);
            $request->setTimeBefore($timeBefore);
            $request->addFilter('_article', 'true');
            $request->addGroup("user_id", "browser_id");

            $pageviews = collect($journalContract->count($request));

            foreach ($pageviews as $record) {
                if ($record->count === 0) {
                    continue;
                }

                if (!empty($record->tags->user_id)) {
                    if (!isset($readers[$record->tags->user_id])) {
                        $readers[$record->tags->user_id] = 0;
                    }
                    if (isset($record->tags->browser_id) && !isset($browsersToUsers[$record->tags->browser_id])) {
                        $browsersToUsers[$record->tags->browser_id] = $record->tags->user_id;
                    }
                    $readers[$record->tags->user_id] += $record->count;
                }

                if (!empty($record->tags->browser_id)) {
                    if (!isset($readers[$record->tags->browser_id])) {
                        $readers[$record->tags->browser_id] = 0;
                    }

                    if (isset($browsersToUsers[$record->tags->browser_id])) {
                        $userId = $browsersToUsers[$record->tags->browser_id];
                        $readers[$userId] += $record->count;
                    } else {
                        $readers[$record->tags->browser_id] += $record->count;
                    }
                }
            }
            $bar->advance();
            break;
        }

        $bar->finish();
        $this->line(' <info>OK!</info>');

        rsort($readers, SORT_NUMERIC);
        $topReaders = array_slice($readers, 0, ceil(count($readers) * 0.1));
        $treshold = array_pop($topReaders);

        $this->line("Top 10% of your readers read at least <info>{$treshold} articles within {$days} days range</info>");

        if ($treshold <= 1) {
            $this->line("No segment will be created, treshold would be too low");
            return 1;
        }

        $segmentCode = "{$treshold}-plus-article-views-in-{$days}-days";
        if (Segment::where(['code' => $segmentCode])->exists()) {
            $this->line("Segment <info>{$segmentCode}</info> already exists, no new segment was created");
            return 2;
        }

        $segment = Segment::create([
            'name' => "{$treshold}+ article views in {$days} days",
            'code' => $segmentCode,
            'active' => true,
            'segment_group_id' => SegmentGroup::getByCode(SegmentGroup::CODE_REMP_SEGMENTS)->id,
        ]);
        $segment->rules()->create([
            'timespan' => $days*24*60,
            'count' => $treshold,
            'event_category' => 'pageview',
            'event_action' => 'load',
            'operator' => '>=',
            'fields' => [],
            'flags' => ['is_article' => '1'],
        ]);

        $this->line("Segment <info>{$segmentCode}</info> was created, you can use it to target your loyal audience");
        return 0;
    }
}
