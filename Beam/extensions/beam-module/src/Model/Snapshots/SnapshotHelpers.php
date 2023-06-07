<?php
namespace Remp\BeamModule\Model\Snapshots;

use Remp\BeamModule\Helpers\Journal\JournalInterval;
use Remp\BeamModule\Model\ArticleViewsSnapshot;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SnapshotHelpers
{
    /**
     * Load concurrents histogram for given interval
     * Concurrents counts are grouped by time and referer_medium
     *
     * @param JournalInterval $interval
     * @param null            $externalArticleId if specified, show histogram only for this article
     * @param bool            $addLastMinute include last minute (for incomplete interval)
     *
     * @return Collection     collection of concurrent time objects (properties: time, count, referer_medium)
     */
    public function concurrentsHistogram(
        JournalInterval $interval,
        $externalArticleId = null,
        bool $addLastMinute = false
    ) {
        /** @var Carbon $from */
        $from = $interval->timeAfter;
        /** @var Carbon $to */
        $to = $interval->timeBefore;

        $timePoints = $this->timePoints($from, $to, $interval->intervalMinutes, $addLastMinute, function (Builder $query) use ($externalArticleId) {
            if ($externalArticleId) {
                $query->where('external_article_id', $externalArticleId);
            }
        });

        $q = ArticleViewsSnapshot::select('time', 'referer_medium', DB::raw('sum(count) as count'))
            ->whereIn('time', $timePoints->getIncludedPoints())
            ->groupBy(['time', 'referer_medium']);

        if ($externalArticleId) {
            $q->where('external_article_id', $externalArticleId);
        }

        $timePointsMapping = $timePoints->getIncludedPointsMapping();

        // get cache key from binded parameters - parameters from scope may occur (e.g. `property_token`)
        $bindings = implode('', $q->getBindings());
        $cacheKey = "concurrentHistogram.". hash('md5', $bindings);

        $concurrents = Cache::get($cacheKey);
        if (!$concurrents) {
            $concurrents = collect();
            foreach ($q->get() as $item) {
                $concurrents->push((object) [
                    // concurrent snapshots may not be stored in DB precisely for each time interval start (e.g. snapshotting took too long).
                    // therefore we use provided mapping to display them nicely in graph
                    'time' => $timePointsMapping[$item->time->toIso8601ZuluString()],
                    'real_time' => $item->time->toIso8601ZuluString(),
                    'count' => $item->count,
                    'referer_medium' => $item->referer_medium,
                ]);
            }
            if ($interval->cacheTTL > 0) {
                Cache::put($cacheKey, $concurrents, $interval->cacheTTL);
            }
        }

        return $concurrents;
    }

    /**
     * Load concurrents histogram for given interval
     * Concurrents counts are grouped by time, referer_medium
     *
     * @param JournalInterval $interval
     * @param array           $externalArticleIds
     *
     * @return ArticleViewsSnapshot[]
     */
    public function concurrentArticlesHistograms(
        JournalInterval $interval,
        array $externalArticleIds
    ) {
        sort($externalArticleIds);
        $cacheKey = "concurrentArticlesHistograms." . hash('md5', implode('.', $externalArticleIds));

        $result = Cache::get($cacheKey);
        if (!$result) {
            /** @var Carbon $from */
            $from = $interval->timeAfter;
            /** @var Carbon $to */
            $to = $interval->timeBefore;

            $q = DB::table(ArticleViewsSnapshot::getTableName())
                ->select('time', 'external_article_id', DB::raw('sum(count) as count'), DB::raw('UNIX_TIMESTAMP(time) as timestamp'))
                ->where('time', '>=', $from)
                ->where('time', '<=', $to)
                ->whereIn('external_article_id', $externalArticleIds)
                ->groupBy(['time', 'external_article_id'])
                ->orderBy('external_article_id')
                ->orderBy('time');

            $result = $q->get();

            // Set 10 minute cache
            Cache::put($cacheKey, $result, 600);
        }

        return $result;
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
     * @return SnapshotTimePoints containing array for included/excluded time points (and mapping to lowest time point)
     */
    public function timePoints(
        Carbon $from,
        Carbon $to,
        int $intervalMinutes,
        bool $addLastMinute = false,
        callable $conditions = null
    ): SnapshotTimePoints {
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

        $timePoints = [];
        foreach ($timeRecords as $timeRecord) {
            $timePoints[$timeRecord->toIso8601ZuluString()] = false;
        }

        $includedPointsMapping = [];

        $timeIterator = clone $from;

        while ($timeIterator->lte($to)) {
            $upperLimit = (clone $timeIterator)->addMinutes($intervalMinutes);

            $i = 0;
            while ($i < count($timeRecords)) {
                if ($timeRecords[$i]->gte($timeIterator) && $timeRecords[$i]->lt($upperLimit)) {
                    $timePoints[$timeRecords[$i]->toIso8601ZuluString()] = true;
                    $includedPointsMapping[$timeRecords[$i]->toIso8601ZuluString()] = $timeIterator->toIso8601ZuluString();
                    break;
                }
                $i++;
            }
            $timeIterator->addMinutes($intervalMinutes);
        }

        // Add last minute
        if ($addLastMinute && count($timeRecords) > 0) {
            // moves the internal pointer to the end of the array
            end($timePoints);
            $timePoints[key($timePoints)] = true;

            if (!isset($includedPointsMapping[key($timePoints)])) {
                $includedPointsMapping[key($timePoints)] = key($timePoints);
            }
        }

        // Filter results
        $includedPoints = [];
        $excludedPoints = [];
        foreach ($timePoints as $timeValue => $isIncluded) {
            if ($isIncluded) {
                $includedPoints[] = $timeValue;
            } else {
                $excludedPoints[] = $timeValue;
            }
        }

        return new SnapshotTimePoints($includedPoints, $excludedPoints, $includedPointsMapping);
    }
}
