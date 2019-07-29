<?php
namespace App\Model\Snapshots;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SnapshotHelpers
{

    /**
     * Inverse function to timePoints(), selects those time points that should be excluded
     * @param Carbon $from
     * @param Carbon $to
     * @param int    $intervalMinutes
     *
     * @return array
     */
    public function timePointsToExclude(Carbon $from, Carbon $to, int $intervalMinutes): array
    {
        $timeRecords = DB::table('article_views_snapshots')
            ->select('time')
            ->whereBetween('time', [$from, $to])
            ->groupBy('time')
            ->get()
            ->map(function ($item) {
                return Carbon::parse($item->time);
            })->toArray();

        $timeIterator = clone $from;

        $points = [];
        $i = 0;

        while ($timeIterator->lte($to)) {
            $upperLimit = (clone $timeIterator)->addMinutes($intervalMinutes - 1);
            $timeIteratorString = $timeIterator->toIso8601ZuluString();

            while ($i < count($timeRecords)) {
                if (array_key_exists($timeIteratorString, $points)) {
                    break;
                }

                if ($timeRecords[$i]->between($timeIterator, $upperLimit)) {
                    $points[$timeIteratorString] = $timeRecords[$i]->toIso8601ZuluString();
                }

                $i++;
            }

            $timeIterator->addMinutes($intervalMinutes);
        }

        $pointsToExclude = [];
        foreach ($points as $point) {
            $pointsToExclude[$point] = true;
        }

        $toReturn = [];
        foreach ($timeRecords as $timeRecord) {
            $timeRecordString = $timeRecord->toIso8601ZuluString();
            if (!array_key_exists($timeRecordString, $pointsToExclude)) {
                $toReturn[] = $timeRecordString;
            }
        }

        return $toReturn;
    }

    /**
     * Computes lowest time point (present in DB) per each $intervalMinutes window, in [$from, $to] interval
     * @param Carbon $from
     * @param Carbon $to
     * @param int    $intervalMinutes
     * @param bool   $addLastMinute
     *
     * @return array
     */
    public function timePoints(Carbon $from, Carbon $to, int $intervalMinutes, bool $addLastMinute = false): array
    {
        $timeRecords = DB::table('article_views_snapshots')
            ->select('time')
            ->whereBetween('time', [$from, $to])
            ->groupBy('time')
            ->get()
            ->map(function ($item) {
                return Carbon::parse($item->time);
            })->toArray();

        $timeIterator = clone $from;

        $points = [];
        $i = 0;

        $lastPoint = null;
        while ($timeIterator->lte($to)) {
            $upperLimit = (clone $timeIterator)->addMinutes($intervalMinutes - 1);
            $timeIteratorString = $timeIterator->toIso8601ZuluString();

            while ($i < count($timeRecords)) {
                if (array_key_exists($timeIteratorString, $points)) {
                    break;
                }

                if ($timeRecords[$i]->between($timeIterator, $upperLimit)) {
                    $points[$timeIteratorString] = $timeRecords[$i];
                    $lastPoint = $timeRecords[$i];
                }

                $i++;
            }

            $timeIterator->addMinutes($intervalMinutes);
        }

        if ($addLastMinute && $lastPoint) {
            if ($timeRecords[count($timeRecords) - 1]->gt($lastPoint)) {
                $points[] = $timeRecords[count($timeRecords) - 1];
            }
        }

        return array_values($points);
    }
}
