<?php

namespace Remp\BeamModule\Helpers\Journal;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\RefererMediumLabel;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Remp\Journal\AggregateRequest;
use Remp\Journal\ConcurrentsRequest;
use Remp\Journal\JournalContract;
use Remp\Journal\ListRequest;

class JournalHelpers
{
    const CATEGORY_ACTION_SEPARATOR = '::';

    private $journal;

    private $cachedRefererMediumLabels;

    public function __construct(JournalContract $journal)
    {
        $this->journal = $journal;
    }

    public function currentConcurrentsCount(callable $conditions = null, Carbon $now = null): Collection
    {
        $timeBefore = $now;
        if ($timeBefore === null) {
            $timeBefore = Carbon::now()->microsecond(0);
        }

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
    public function uniqueBrowsersCountForArticles(Collection $articles): Collection
    {
        $minimalPublishedTime = Carbon::now();
        foreach ($articles as $article) {
            if ($minimalPublishedTime->gt($article->published_at)) {
                $minimalPublishedTime = $article->published_at;
            }
        }

        // sort article IDs to help journal caching mechanism cache this payload
        $externalArticleIds = $articles->pluck('external_id')->sortDesc(SORT_NUMERIC)->toArray();

        $r = (new AggregateRequest('pageviews', 'load'))
            ->setTime($minimalPublishedTime, Carbon::now())
            ->addGroup('article_id')
            ->addFilter('article_id', ...$externalArticleIds);

        $result = collect($this->journal->unique($r));
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
        $externalArticleIds = $articles->pluck('external_id')->sortDesc(SORT_NUMERIC)->toArray();

        $cacheKey = "abTestFlagsForArticles." . hash('md5', implode('', $externalArticleIds));
        $result = Cache::get($cacheKey);
        if (!$result) {
            $request = new AggregateRequest('pageviews', 'load');
            $request->setTimeAfter($since);
            $request->setTimeBefore(Carbon::now());
            $request->addGroup('article_id', 'title_variant', 'image_variant');

            $request->addFilter('article_id', ...$externalArticleIds);

            $result = collect($this->journal->count($request));

            // Set 10 minutes cache
            Cache::put($cacheKey, $result, 600);
        }

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

    public function refererMediumLabel(string $medium, bool $cached = true): string
    {
        if (!$cached || !$this->cachedRefererMediumLabels) {
            $this->cachedRefererMediumLabels = RefererMediumLabel::all();
        }

        foreach ($this->cachedRefererMediumLabels as $row) {
            if ($medium === $row->referer_medium) {
                return $row->label;
            }
        }

        // TODO: extract to seeders
        // Built-in labels
        switch ($medium) {
            case 'direct':
                return 'direct/IM';
            default:
                return $medium;
        }
    }

    /**
     * Returns list of all categories and actions for stored events
     * @param \Remp\BeamModule\Model\Article|null $article if provided, load events data only for particular article
     *
     * @return array array of objects having category and action parameters
     */
    public function eventsCategoriesActions(?Article $article = null): array
    {
        $r = AggregateRequest::from('events')->addGroup('category', 'action');

        if ($article !== null) {
            $r->addFilter('article_id', $article->external_id);
        }

        $results = [];

        foreach ($this->journal->count($r) as $item) {
            if (!empty($item->tags->action) && !empty($item->tags->category)) {
                $results[] = (object) $item->tags;
            }
        }
        return $results;
    }


    /**
     * @param JournalInterval $journalInterval load events only for particular interval
     * @param string[]        $requestedCategoryActionEvents array of strings having format 'category::action'
     * @param \Remp\BeamModule\Model\Article|null    $article if provided, load only events linked to particular article
     *
     * @return array List of events as provided by Journal API
     */
    public function loadEvents(JournalInterval $journalInterval, array $requestedCategoryActionEvents, ?Article $article = null): array
    {
        if (!$requestedCategoryActionEvents) {
            return [];
        }

        $categories = [];
        $categoryActions = [];
        foreach ($requestedCategoryActionEvents as $item) {
            [$category, $action] = explode(self::CATEGORY_ACTION_SEPARATOR, $item);
            $categories[] = $category;
            if (! array_key_exists($category, $categoryActions)) {
                $categoryActions[$category] = [];
            }
            $categoryActions[$category][] = $action;
        }

        $r = ListRequest::from('events')
            ->setTime($journalInterval->timeAfter, $journalInterval->timeBefore)
            ->addFilter('category', ...$categories);

        if ($article) {
            $r->addFilter('article_id', $article->external_id);
        }

        $response = $this->journal->list($r);

        $events = [];

        foreach ($response[0]->events ?? [] as $event) {
            if (! in_array($event->action, $categoryActions[$event->category], true)) {
                // ATM Journal API doesn't provide way to simultaneously check for category and action on the same record,
                // therefore check first category in the request and check action here
                continue;
            }
            $events[] = $event;
        }
        return $events;
    }
}
