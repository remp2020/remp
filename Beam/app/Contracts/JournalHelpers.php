<?php

namespace App\Contracts;

use App\Article;
use App\Conversion;
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

    public function actionsPriorConversion(
        Conversion $conversion,
        $daysInPast = 2,
        $loadTimespent = false,
        $loadArticles = false
    ): Collection {
    
        $timeBefore = clone $conversion->paid_at;
        $timeAfter = (clone $timeBefore)->subDays($daysInPast);
        $actions = collect();

        // Commerce
        $commerces = $this->journal->list(JournalListRequest::from('commerce')
            ->addFilter('user_id', $conversion->user_id)
            ->setTimeAfter($timeAfter)
            ->setTimeBefore($timeBefore));

        if ($commerces->isNotEmpty()) {
            foreach ($commerces[0]->commerces as $item) {
                if (isset($item->system->time, $item->step)) {
                    $obj = new \stdClass();
                    $obj->time = $item->system->time;
                    $obj->action = "commerce:{$item->step}";
                    $actions->push($obj);
                }
            }
        }

        // Events
        $events = $this->journal->list(JournalListRequest::from('events')
            ->addFilter('user_id', $conversion->user_id)
            ->setTimeAfter($timeAfter)
            ->setTimeBefore($timeBefore));

        if ($events->isNotEmpty()) {
            foreach ($events[0]->events as $item) {
                if (isset($item->system->time, $item->action, $item->category)) {
                    $obj = new \stdClass();
                    $obj->time = $item->system->time;
                    $obj->action = "{$item->action}:{$item->category}";
                    $actions->push($obj);
                }
            }
        }

        $articleIds = [];

        // Pageviews
        $r = JournalListRequest::from('pageviews')
            ->addFilter('user_id', $conversion->user_id)
            ->setTimeAfter($timeAfter)
            ->setTimeBefore($timeBefore);

        if ($loadTimespent) {
            $r->setLoadTimespent();
        }

        $pageviews = $this->journal->list($r);

        if ($pageviews->isNotEmpty()) {
            foreach ($pageviews[0]->pageviews as $item) {
                if (isset($item->system->time, $item->article->id, $item->user->remp_pageview_id)) {
                    $obj = new \stdClass();
                    $obj->time = $item->system->time;
                    $obj->action = 'pageview';
                    $obj->article_id = $item->article->id;
                    $obj->pageview_id = $item->user->remp_pageview_id;
                    if (isset($item->user->timespent)) {
                        $obj->timespent = $item->user->timespent;
                    }
                    $articleIds[] = $obj->article_id;
                    $actions->push($obj);
                }
            }
        }

        if ($loadArticles) {
            $articles = Article::whereIn('external_id', $articleIds)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->external_id => $item];
                });

            foreach ($actions as $action) {
                if ($action->action === 'pageview') {
                    if ($articles->has($action->article_id)) {
                        $action->article = $articles->get($action->article_id);
                    }
                }
            }
        }

        $actions = $actions->sortByDesc('time');

        return $actions;
    }
}
