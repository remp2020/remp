<?php
namespace App\Model\Snapshots;

use App\Helpers\Journal\JournalInterval;
use App\Model\ArticleViewsSnapshot;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SnapshotHelpers
{
    /**
     * Load concurrents histogram for given interval
     * Concurrents counts are grouped by time, referer_medium
     * TODO: add caching
     *
     * @param JournalInterval $interval
     * @param null            $externalArticleId if specified, show histogram only for this article
     * @param bool            $addLastMinute include last minute (for incomplete interval)
     *
     * @return ArticleViewsSnapshot[]
     */
    public function concurrentsHistogram(JournalInterval $interval, $externalArticleId = null, bool $addLastMinute = false)
    {
        /** @var Carbon $from */
        $from = $interval->timeAfter->tz('UTC');
        /** @var Carbon $to */
        $to = $interval->timeBefore->tz('UTC');

        $timePoints = $this->timePoints($from, $to, $interval->intervalMinutes, $addLastMinute, function (Builder $query) use ($externalArticleId) {
            if ($externalArticleId) {
                $query->where('external_article_id', $externalArticleId);
            }
        });

        $q = ArticleViewsSnapshot::select('time', 'referer_medium', DB::raw('sum(count) as count'))
            ->whereIn('time', $timePoints->toInclude)
            ->groupBy(['time', 'referer_medium']);

        if ($externalArticleId) {
            $q->where('external_article_id', $externalArticleId);
        }
        return $q->get();
    }

    /**
     * Computes lowest time point (present in DB) per each $intervalMinutes window, in [$from, $to] interval
     *
     * @param Carbon        $from (including)
     * @param Carbon        $to   (excluding)
     * @param int           $intervalMinutes
     * @param bool          $addLastMinute
     * @param callable|null $conditions
     *
     * @return TimePoints containing array for included/excluded time points
     */
    public function timePoints(
        Carbon $from,
        Carbon $to,
        int $intervalMinutes,
        bool $addLastMinute = false,
        callable $conditions = null
    ): TimePoints {
    
        $q = DB::table(ArticleViewsSnapshot::getTableName())
            ->select('time')
            ->where('time', '>=', $from)
            ->where('time', '<=', $to)
            ->groupBy('time')
            ->orderBy('time');

        if ($conditions) {
            $conditions($q);
        }

        $timeRecords = $q->get()
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
