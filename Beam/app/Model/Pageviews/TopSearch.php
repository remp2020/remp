<?php

namespace App\Model\Pageviews;

use DB;
use Illuminate\Support\Carbon;

class TopSearch
{
    public function topArticles($timeFrom, $limit, $sections)
    {
        $getTopArticlesFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) use ($sections) {
            $pageviews = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->groupBy('article_pageviews.article_id')
                ->select(
                    'article_pageviews.article_id',
                    DB::raw('SUM(article_pageviews.sum) as pageviews')
                )
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            if ($sections) {
                $pageviews->join('article_section', 'article_pageviews.article_id', '=', 'article_section.article_id')
                    ->join('sections', 'article_section.section_id', '=', 'sections.id')
                    ->whereIn('sections.name', $sections);
            }

            $data = DB::table('articles')
                ->joinSub($pageviews, 'top_articles_by_pageviews', function ($join) {
                    $join->on('articles.id', '=', 'top_articles_by_pageviews.article_id');
                })
                ->select('articles.external_id', 'top_articles_by_pageviews.pageviews');

            return $data->get()->map(function ($article) {
                $article->pageviews = (int) $article->pageviews;
                return $article;
            });
        };

        return $this->queryTopPageviewItemsByDays($timeFrom, $limit, $getTopArticlesFunc);
    }

    public function topAuthors($timeFrom, $limit)
    {
        $getTopAuthorsFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) {
            $topAuthorsQuery = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->join('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
                ->groupBy(['article_author.author_id'])
                ->select('article_author.author_id', DB::raw('SUM(article_pageviews.sum) as pageviews'))
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            $data = DB::table('authors')
                ->joinSub($topAuthorsQuery, 'top_authors', function ($join) {
                    $join->on('authors.id', '=', 'top_authors.author_id');
                })
                ->select('authors.external_id', 'authors.name', 'top_authors.pageviews');

            return $data->get()->map(function ($item) {
                $item->pageviews = (int) $item->pageviews;
                return $item;
            });
        };

        return $this->queryTopPageviewItemsByDays($timeFrom, $limit, $getTopAuthorsFunc, 'name');
    }

    public function topPostTags($timeFrom, $limit)
    {
        $getTopPostTagsFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) {
            $topTagsQuery = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->join('article_tag', 'article_pageviews.article_id', '=', 'article_tag.article_id')
                ->groupBy(['article_tag.tag_id'])
                ->select('article_tag.tag_id', DB::raw('SUM(article_pageviews.sum) as pageviews'))
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            $data = DB::table('tags')
                ->joinSub($topTagsQuery, 'top_tags', function ($join) {
                    $join->on('tags.id', '=', 'top_tags.tag_id');
                })
                ->select('tags.name', 'top_tags.pageviews');

            return $data->get()->map(function ($item) {
                $item->pageviews = (int) $item->pageviews;
                return $item;
            });
        };

        // TODO: currently tags do not have external_id, using 'name' as key attribute
        return $this->queryTopPageviewItemsByDays($timeFrom, $limit, $getTopPostTagsFunc, 'name');
    }

    // split query by days (due to speed)
    private function queryTopPageviewItemsByDays(Carbon $timeFrom, $limit, callable $queryFunction, $keyAttribute = 'external_id')
    {
        $timeTo = (clone $timeFrom)->modify('+1 day');
        $now = Carbon::now();

        // do not split query if time_from -> now is less than day
        if ($timeTo > $now) {
            return $queryFunction($timeFrom, $now, $limit);
        }

        $results = [];
        while ($timeTo < $now) {
            $items = $queryFunction($timeFrom, $timeTo, $limit * 2);
            foreach ($items as $item) {
                if (!isset($results[$item->$keyAttribute])) {
                    $results[$item->$keyAttribute] = $item;
                    continue;
                }

                $results[$item->$keyAttribute]->pageviews += $item->pageviews;
            }

            $timeFrom->modify('+1 day');
            $timeTo->modify('+1 day');
            if ($timeTo > $now) {
                $timeTo = $now;
            }
        }

        usort($results, function ($a, $b) {
            return $a->pageviews < $b->pageviews;
        });

        return array_slice($results, 0, $limit);
    }
}
