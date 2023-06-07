<?php

namespace Remp\BeamModule\Console\Commands;

use Remp\BeamModule\Helpers\Journal\JournalInterval;
use Remp\BeamModule\Model\ArticleViewsSnapshot;
use Remp\BeamModule\Model\Snapshots\SnapshotHelpers;
use Carbon\Carbon;
use Remp\BeamModule\Console\Command;

class CompressSnapshots extends Command
{
    const COMMAND = 'pageviews:compress-snapshots';

    protected $signature = self::COMMAND . ' {--now=}';

    protected $description = 'Compress snapshots according to the retention rules';

    private $snapshotHelpers;

    public function __construct()
    {
        parent::__construct();
        $this->snapshotHelpers = new SnapshotHelpers();
    }

    public function handle()
    {
        $this->line('');
        $this->line('<info>***** Compressing snapshots *****</info>');
        $this->line('');

        // Start compression from last hour (to avoid complicated rounding of retention intervals, to get "nice" datetimes in dashboard)
        $now = Carbon::now();
        $now->setTime($now->hour, 0);

        // $now can be optionally rewritten (useful in tests)
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : $now;

        $this->compress($now);

        $this->line(' <info>OK!</info>');
        return 0;
    }

    private function compress(Carbon $now)
    {
        foreach (JournalInterval::RETENTION_RULES as $rule) {
            $startMinute = $rule[0];
            $endMinute = $rule[1];
            $windowSizeInMinutes = $rule[2];

            $this->line("Applying retention rule [{$startMinute}m, " . ($endMinute ?? 'unlimited ') . "m), window size $windowSizeInMinutes minutes");

            $periods = $this->computeDayPeriods($now, $startMinute, $endMinute);
            foreach ($periods as $period) {
                $from = $period[0];
                $to = $period[1];

                $this->line("Compressing snapshots between {$from} and {$to}");

                $timePoints = $this->snapshotHelpers->timePoints($from, $to, $windowSizeInMinutes);

                $excludedTimePoints = array_map(function (string $zuluTimeString) {
                    return Carbon::parse($zuluTimeString);
                }, $timePoints->getExcludedPoints());

                foreach (array_chunk($excludedTimePoints, 200) as $excludedTimePointsChunk) {
                    $deletedCount = ArticleViewsSnapshot::deleteForTimes($excludedTimePointsChunk);
                    if ($deletedCount > 0) {
                        $this->line("$deletedCount records deleted");
                    }
                }
            }
        }
    }

    /**
     * Function that computes interval [$now-$endMinute, $now-$startMinute] and splits it to days
     * @param Carbon   $now
     * @param int      $startMinute
     * @param int|null $endMinute
     *
     * @return array
     */
    public function computeDayPeriods(Carbon $now, int $startMinute, ?int $endMinute): array
    {
        $periods = [];

        $to = (clone $now)->subMinutes($startMinute);
        if ($endMinute !== null) {
            $from = (clone $now)->subMinutes($endMinute);
        } else {
            // If $endMinute is null, do not construct unlimited interval,
            // go back only few days since the previous values have already been compressed
            $from = (clone $to)->subDays(2);
        }

        $timeIterator = clone $from;

        // Split by days
        while ($timeIterator->lt($to)) {
            $end = (clone $timeIterator)->addDay();
            $periods[] = [clone $timeIterator, $to->lt($end) ? $to : $end];
            $timeIterator->addDay();
        }

        return $periods;
    }
}
