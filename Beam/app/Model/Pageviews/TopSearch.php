<?php

namespace App\Model\Pageviews;

use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class TopSearch
{
    public function topArticles($timeFrom, $limit, string $sectionValueType = null, ?array $sectionValues = null, ?string $contentType = null)
    {
        $getTopArticlesFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) use ($sectionValueType, $sectionValues, $contentType) {
            $pageviewsQuery = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->groupBy('article_pageviews.article_id')
                ->select(
                    'article_pageviews.article_id',
                    DB::raw('SUM(article_pageviews.sum) as pageviews')
                )
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            if ($sectionValueType && $sectionValues) {
                $this->addSectionsCondition($pageviewsQuery, $sectionValueType, $sectionValues);
            }
            if ($contentType) {
                $this->addContentTypeCondition($pageviewsQuery, $contentType);
            }

            $data = DB::table('articles')
                ->joinSub($pageviewsQuery, 'top_articles_by_pageviews', function ($join) {
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

    public function topAuthors($timeFrom, $limit, string $sectionValueType = null, ?array $sectionValues = null, ?string $contentType = null)
    {
        $getTopAuthorsFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) use ($sectionValueType, $sectionValues, $contentType) {
            $topAuthorsQuery = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->join('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
                ->groupBy(['article_author.author_id'])
                ->select('article_author.author_id', DB::raw('SUM(article_pageviews.sum) as pageviews'))
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            if ($sectionValueType && $sectionValues) {
                $this->addSectionsCondition($topAuthorsQuery, $sectionValueType, $sectionValues);
            }
            if ($contentType) {
                $this->addContentTypeCondition($topAuthorsQuery, $contentType);
            }

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

    public function topPostTags($timeFrom, $limit, string $sectionValueType = null, ?array $sectionValues = null, ?string $contentType = null)
    {
        $getTopPostTagsFunc = function (Carbon $timeFrom, Carbon $timeTo, $limit) use ($sectionValueType, $sectionValues, $contentType) {
            $topTagsQuery = DB::table('article_pageviews')
                ->where('article_pageviews.time_from', '>=', $timeFrom)
                ->where('article_pageviews.time_from', '<=', $timeTo)
                ->join('article_tag', 'article_pageviews.article_id', '=', 'article_tag.article_id')
                ->groupBy(['article_tag.tag_id'])
                ->select('article_tag.tag_id', DB::raw('SUM(article_pageviews.sum) as pageviews'))
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);

            if ($sectionValueType && $sectionValues) {
                $this->addSectionsCondition($topTagsQuery, $sectionValueType, $sectionValues);
            }
            if ($contentType) {
                $this->addContentTypeCondition($topTagsQuery, $contentType);
            }

            $data = DB::table('tags')
                ->joinSub($topTagsQuery, 'top_tags', function ($join) {
                    $join->on('tags.id', '=', 'top_tags.tag_id');
                })
                ->select('tags.name', 'tags.external_id', 'top_tags.pageviews');

            return $data->get()->map(function ($item) {
                $item->pageviews = (int) $item->pageviews;
                return $item;
            });
        };

        // TODO: currently tags may not have external_id, using 'name' as key attribute
        return $this->queryTopPageviewItemsByDays($timeFrom, $limit, $getTopPostTagsFunc, 'name');
    }

    private function addSectionsCondition(Builder $articlePageviewsQuery, string $type, array $values)
    {
        if (!in_array($type, ['external_id', 'name'], true)) {
            throw new \Exception("type '$type' is not one of 'external_id' or 'name' values");
        }

        $articlePageviewsQuery->join('article_section', 'article_pageviews.article_id', '=', 'article_section.article_id')
            ->join('sections', 'article_section.section_id', '=', 'sections.id')
            ->whereIn('sections.' . $type, $values);
    }

    private function addContentTypeCondition(Builder $articlePageviewsQuery, string $contentType)
    {
        $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id')
            ->where('articles.content_type', '=', $contentType);
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
