<?php

namespace App\Helpers\Journal;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Remp\Journal\AggregateRequest;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;

class JournalHelpers
{
    private $journal;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    public function currentConcurrentsCount(callable $conditions = null): Collection
    {
        $timeBefore = Carbon::now();
        $timeAfter = (clone $timeBefore)->subSeconds(600); // Last 10 minutes
        $req = new ConcurrentsRequest();
        $req->setTimeAfter($timeAfter);
        $req->setTimeBefore($timeBefore);

        if ($conditions) {
            $conditions($req);
        }

        return collect($this->journal->concurrents($req));
    }

    /**
     * Get available referer mediums in pageviews segments storage per last $hoursAgo
     * Useful for filtering in data tables
     * @param int $hoursAgo
     *
     * @return Collection
     */
    public function derivedRefererMediumGroups(int $hoursAgo = 12): Collection
    {
        $ar = (new AggregateRequest('pageviews', 'load'))
            ->setTime(Carbon::now()->subHours($hoursAgo), Carbon::now())
            ->addGroup('derived_referer_medium');

        $results = collect($this->journal->count($ar));
        return $results->pluck('tags.derived_referer_medium');
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

        $request = new AggregateRequest('pageviews', 'load');
        $request->setTimeAfter($minimalPublishedTime);
        $request->setTimeBefore($timeBefore);
        $request->addGroup('article_id');
        $request->addFilter('article_id', ...$externalArticleIds);

        $result = collect($this->journal->unique($request));
        return $result
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

        $request = new AggregateRequest('pageviews', 'timespent');
        $request->setTimeAfter($since);
        $request->setTimeBefore(Carbon::now());
        $request->addGroup('article_id');

        $request->addFilter('article_id', ...$externalArticleIds);

        $result = collect($this->journal->avg($request));
        return $result
            ->filter(function ($item) {
                return $item->tags !== null;
            })
            ->mapWithKeys(function ($item) {
                return [$item->tags->article_id => $item->avg];
            });
    }

    /**
     * Load a/b test flags for articles
     *
     * @param Collection $articles
     * @param Carbon     $since
     *
     * @return Collection containing mapping of articles' external_ids to a/b test flags
     */
    public function abTestFlagsForArticles(Collection $articles, Carbon $since): Collection
    {
        $externalArticleIds = $articles->pluck('external_id')->toArray();

        $request = new AggregateRequest('pageviews', 'load');
        $request->setTimeAfter($since);
        $request->setTimeBefore(Carbon::now());
        $request->addGroup('article_id', 'title_variant', 'image_variant');

        $request->addFilter('article_id', ...$externalArticleIds);

        $result = collect($this->journal->count($request));
        $articles = collect();

        foreach ($result as $item) {
            $key = $item->tags->article_id;
            if (!$articles->has($key)) {
                $articles->put($key, (object) [
                    'title_variants' => collect(),
                    'image_variants' => collect()
                ]);
            }
            $articles[$key]->title_variants->push($item->tags->title_variant);
            $articles[$key]->image_variants->push($item->tags->image_variant);
        }

        return $articles->map(function ($item) {
            $hasTitleTest = $item->title_variants->filter(function ($variant) {
                return $variant !== '';
            })->unique()->count() > 1;

            $hasImageTest = $item->image_variants->filter(function ($variant) {
                    return $variant !== '';
            })->unique()->count() > 1;

            return (object) [
                'has_title_test' => $hasTitleTest,
                'has_image_test' => $hasImageTest
            ];
        });
    }

    /**
     * Get time iterator, which is the earliest point of time (Carbon instance) that when
     * interval of length $intervalMinutes is added,
     * the resulting Carbon instance is greater or equal to $timeAfter
     *
     * This is useful for preparing data for histogram graphs
     *
     * @param Carbon                $timeAfter
     * @param int                   $intervalMinutes
     * @param \DateTimeZone|string  $tz                 Default value is `UTC`.
     *
     * @return Carbon
     */
    public static function getTimeIterator(Carbon $timeAfter, int $intervalMinutes, $tz = 'UTC'): Carbon
    {
        $timeIterator = (clone $timeAfter)->tz($tz)->startOfDay();
        while ($timeIterator->lessThanOrEqualTo($timeAfter)) {
            $timeIterator->addMinutes($intervalMinutes);
        }
        return $timeIterator->subMinutes($intervalMinutes);
    }
}
