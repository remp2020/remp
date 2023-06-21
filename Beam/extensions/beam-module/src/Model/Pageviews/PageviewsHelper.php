<?php
namespace Remp\BeamModule\Model\Pageviews;

use Remp\BeamModule\Model\ArticlePageviews;
use Remp\BeamModule\Helpers\Journal\JournalInterval;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PageviewsHelper
{
    /**
     * Load concurrents histogram for given interval
     * Concurrents counts are grouped by time and referer_medium
     *
     * @param JournalInterval $interval
     * @param ?int            $articleId if specified, show histogram only for this article
     *
     * @return Collection     collection of concurrent time objects (properties: time, sum)
     */
    public function pageviewsHistogram(JournalInterval $interval, ?int $articleId = null)
    {
        $from = $interval->timeAfter;
        $to = $interval->timeBefore;

        $interval->setIntervalMinutes($this->getInterval($articleId, $from, $to, $interval->intervalMinutes));
        $data = $this->loadIntervalData($articleId, $from, $to, $interval->intervalMinutes);

        $concurrents = collect();
        foreach ($data as $pageviewRecord) {
            $concurrents->push((object) [
                'time' => $pageviewRecord->interval_from,
                'count' => $pageviewRecord->sum,
            ]);
        }

        return $concurrents;
    }

    private function loadIntervalData($articleId, Carbon $from, Carbon $to, int $interval)
    {
        $articleId = $articleId ?? true;
        [$fromIsoString, $toIsoString] = $this->getRoundIsoDates($from, $to, $interval);

        $sql = <<<SQL
WITH RECURSIVE seq AS (SELECT ? AS interval_from, TIMESTAMPADD(MINUTE , ?, ?) AS interval_to
                       UNION ALL
                       SELECT TIMESTAMPADD(MINUTE , ?, interval_from), TIMESTAMPADD(MINUTE , ?, interval_to)
                       FROM seq
                       WHERE interval_to <= ?)
SELECT /*+ SET_VAR(cte_max_recursion_depth = 1M) */
       seq.interval_from,
       seq.interval_to,
       COALESCE(SUM(article_pageviews.sum), 0) as sum
FROM seq
         LEFT JOIN article_pageviews ON (
            article_pageviews.article_id = ? AND article_pageviews.time_from >= seq.interval_from AND article_pageviews.time_to <= seq.interval_to
        )
GROUP BY seq.interval_from, seq.interval_to
SQL;
        return DB::select($sql, [
            $fromIsoString,
            $interval,
            $fromIsoString,
            $interval,
            $interval,
            $toIsoString,
            $articleId,
        ]);
    }

    private function getInterval($articleId, $from, $to, $intervalMinutes)
    {
        $maxInterval = $this->getMaxIntervalAvailable($articleId, $from, $to);
        $interval = $maxInterval ?? $intervalMinutes;
        return min($interval, 1440);
    }

    private function getMaxIntervalAvailable($articleId, Carbon $from, Carbon $to)
    {
        return ArticlePageviews::where('article_id', $articleId)
            ->where('time_from', '>=', $from)
            ->where('time_to', '<=', $to)
            ->max(DB::raw('TIMESTAMPDIFF(MINUTE, time_from, time_to)'));
    }

    private function getRoundIsoDates($from, $to, $interval)
    {
        switch ($interval) {
            case 1440:
                return [
                    $from->floorDay()->toIso8601String(),
                    $to->ceilDay()->toIso8601String(),
                ];
            case 60:
                return [
                    $from->floorHour()->toIso8601String(),
                    $to->ceilHour()->toIso8601String(),
                ];
            case 20:
                return [
                    $from->floorMinutes(20)->toIso8601String(),
                    $to->ceilMinutes(20)->toIso8601String(),
                ];
            default:
                return [
                    $from->floorDay()->toIso8601String(),
                    $to->ceilDay()->toIso8601String(),
                ];
        }
    }
}
