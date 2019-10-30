<?php
namespace App\Model\Snapshots;

use App\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SnapshotHelpers
{
    /**
     * Computes lowest time point (present in DB) per each $intervalMinutes window, in [$from, $to] interval
     *
     * @param Carbon $from (including)
     * @param Carbon $to   (excluding)
     * @param int    $intervalMinutes
     * @param bool   $addLastMinute
     *
     * @return TimePoints containing array for included/excluded time points
     */
    public function timePoints(Carbon $from, Carbon $to, int $intervalMinutes, bool $addLastMinute = false): TimePoints
    {
        $timeRecords = DB::table(ArticleViewsSnapshot::getTableName())
            ->select('time')
            ->where('time', '>=', $from)
            ->where('time', '<=', $to)
            ->groupBy('time')
            ->orderBy('time')
            ->get()
            ->map(function ($item) {
                return Carbon::parse($item->time);
            })->toArray();

        $includedTimes = [];
        foreach ($timeRecords as $timeRecord) {
            $includedTimes[$timeRecord->toIso8601ZuluString()] = false;
        }

        $timeIterator = clone $from;

        while ($timeIterator->lte($to)) {
            $upperLimit = (clone $timeIterator)->addMinutes($intervalMinutes);

            $i = 0;
            while ($i < count($timeRecords)) {
                if ($timeRecords[$i]->gte($timeIterator) && $timeRecords[$i]->lt($upperLimit)) {
                    $includedTimes[$timeRecords[$i]->toIso8601ZuluString()] = true;
                    break;
                }
                $i++;
            }
            $timeIterator->addMinutes($intervalMinutes);
        }

        // Add last minute
        if ($addLastMinute && count($timeRecords) > 0) {
            // moves the internal pointer to the end of the array
            end($includedTimes);
            $includedTimes[key($includedTimes)] = true;
        }

        // Filter results
        $toInclude = [];
        $toExclude = [];
        foreach ($includedTimes as $timeValue => $isIncluded) {
            if ($isIncluded) {
                $toInclude[] = $timeValue;
            } else {
                $toExclude[] = $timeValue;
            }
        }

        return new TimePoints($toInclude, $toExclude);
    }
}
