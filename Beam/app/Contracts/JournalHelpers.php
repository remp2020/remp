<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class JournalHelpers
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    /**
     * Load unique users count per each article
     *
     * @param Collection $articles
     *
     * @return Collection containing mapping of articles' external_ids to unique users count
     */
    public function uniqueUsersCountForArticles(Collection $articles): Collection
    {
        $minimalPublishedTime = Carbon::now();
        foreach ($articles as $article) {
            if ($minimalPublishedTime->gt($article->published_at)) {
                $minimalPublishedTime = $article->published_at;
            }
        }
        $timeBefore = Carbon::now();

        $externalArticleIds = $articles->pluck('external_id')->toArray();

        $request = new JournalAggregateRequest('pageviews', 'load');
        $request->setTimeAfter($minimalPublishedTime);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id');
        $request->addFilter('article_id', ...$externalArticleIds);
        return $this->journal->unique($request)
            ->filter(function ($item) {
                return $item->tags !== null;
            })
            ->mapWithKeys(function ($item) {
                return [$item->tags->article_id => $item->count];
            });
    }

    /**
     * Load timespent count per each article
     *
     * @param Collection $articles
     * @param Carbon     $since
     *
     * @return Collection containing mapping of articles' external_ids to timespent in seconds
     */
    public function timespentForArticles(Collection $articles, Carbon $since): Collection
    {
        $externalArticleIds = $articles->pluck('external_id')->toArray();

        $request = new JournalAggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($since);
        $request->setTimeBefore(Carbon::now());
        $request->addGroup('article_id');

        $request->addFilter('article_id', ...$externalArticleIds);
        return $this->journal->avg($request)
            ->filter(function ($item) {
                return $item->tags !== null;
            })
            ->mapWithKeys(function ($item) {
                return [$item->tags->article_id => $item->avg];
            });
    }

    /**
     * Get time iterator, which is the earliest point of time (Carbon instance) that when
     * interval of length $intervalMinutes is added,
     * the resulting Carbon instance is greater or equal to $timeAfter
     *
     * This is useful for preparing data for histogram graphs
     *
     * @param Carbon $timeAfter
     * @param int    $intervalMinutes
     *
     * @return Carbon
     */
    public static function getTimeIterator(Carbon $timeAfter, int $intervalMinutes): Carbon
    {
        $timeIterator = (clone $timeAfter)->tz('UTC')->startOfDay();
        while ($timeIterator->lessThanOrEqualTo($timeAfter)) {
            $timeIterator->addMinutes($intervalMinutes);
        }
        return $timeIterator->subMinutes($intervalMinutes);
    }
}
