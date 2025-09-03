<?php

namespace Remp\BeamModule\Model\Pageviews\Api\v2;

use Cache;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Remp\BeamModule\Http\Requests\Api\v2\TopArticlesSearchRequest;
use Remp\BeamModule\Http\Requests\Api\v2\TopAuthorsSearchRequest;
use Remp\BeamModule\Http\Requests\Api\v2\TopTagsSearchRequest;

class TopSearch
{
    private const THRESHOLD_DIFF_DAYS = 7;

    public function topArticles(TopArticlesSearchRequest $request)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));
        $timeTo = $request->json('to') ? Carbon::parse($request->json('to')) : null;
        $publishedFrom = $request->json('published_from') ? Carbon::parse($request->json('published_from')) : null;
        $publishedTo = $request->json('published_to') ? Carbon::parse($request->json('published_to')) : null;

        $useArticlesTableAsDataSource = $this->shouldUseArticlesTableAsDataSource($timeFrom, $publishedFrom, $timeTo);

        if ($useArticlesTableAsDataSource) {
            $baseQuery = DB::table('articles')
                ->orderBy('pageviews_all', 'DESC')
                ->limit($limit);
        } else {
            $baseQuery = DB::table('article_pageviews')
                ->groupBy('article_pageviews.article_id')
                ->select(
                    'article_pageviews.article_id',
                    DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews')
                )
                ->orderBy('pageviews', 'DESC')
                ->limit($limit);
        }

        $sectionFilters = $this->getFilters($request, 'sections');
        $this->addSectionsCondition($baseQuery, $sectionFilters, $useArticlesTableAsDataSource);

        $authorFilters = $this->getFilters($request, 'authors');
        $this->addAuthorsCondition($baseQuery, $authorFilters, $useArticlesTableAsDataSource);

        $tagFilters = $this->getFilters($request, 'tags');
        $this->addTagsCondition($baseQuery, $tagFilters, $useArticlesTableAsDataSource);

        $tagCategoryFilters = $this->getFilters($request, 'tag_categories');
        $this->addTagCategoriesCondition($baseQuery, $tagCategoryFilters, $useArticlesTableAsDataSource);

        $contentType = $request->json('content_type');
        if ($contentType) {
            $this->addContentTypeCondition($baseQuery, $contentType, $useArticlesTableAsDataSource);
        }

        if ($publishedFrom) {
            $this->addPublishedFromCondition($baseQuery, $publishedFrom, $useArticlesTableAsDataSource);
        }
        if ($publishedTo) {
            $this->addPublishedToCondition($baseQuery, $publishedTo, $useArticlesTableAsDataSource);
        }

        if (!$useArticlesTableAsDataSource) {
            if ($timeTo === null) {
                $timeTo = Carbon::now();
            }

            $diffInDays = $timeFrom->diffInDays($timeTo);

            // for longer time periods irrelevant low pageviews data will be cut off
            if ($diffInDays > self::THRESHOLD_DIFF_DAYS) {
                $thresholdPageviews = $this->getPageviewsThreshold(clone $baseQuery, $timeTo, $limit);

                if (!$this->hasAlreadyJoinedArticlesTable($baseQuery)) {
                    $baseQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id');
                }

                $baseQuery->where('articles.pageviews_all', '>=', $thresholdPageviews);
            }

            $baseQuery->where('article_pageviews.time_from', '>=', $timeFrom);
            if ($timeTo !== null) {
                $baseQuery->where('article_pageviews.time_to', '<', $timeTo);
            }

            $data = DB::table('articles')
                ->joinSub($baseQuery, 'top_articles_by_pageviews', function ($join) {
                    $join->on('articles.id', '=', 'top_articles_by_pageviews.article_id');
                })
                ->select('articles.id', 'articles.external_id', 'top_articles_by_pageviews.pageviews');
        } else {
            $data = $baseQuery->select('articles.id', 'articles.external_id', 'articles.pageviews_all as pageviews');
        }

        return $data->get();
    }

    public function topAuthors(TopAuthorsSearchRequest $request)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        $topAuthorsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->join('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
            ->groupBy(['article_author.author_id'])
            ->select('article_author.author_id', DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews'))
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        $sectionFilters = $this->getFilters($request, 'sections');
        $this->addSectionsCondition($topAuthorsQuery, $sectionFilters);

        $tagFilters = $this->getFilters($request, 'tags');
        $this->addTagsCondition($topAuthorsQuery, $tagFilters);

        $tagCategoryFilters = $this->getFilters($request, 'tag_categories');
        $this->addTagCategoriesCondition($topAuthorsQuery, $tagCategoryFilters);

        $contentType = $request->json('content_type');
        if ($contentType) {
            $this->addContentTypeCondition($topAuthorsQuery, $contentType);
        }

        $data = DB::table('authors')
            ->joinSub($topAuthorsQuery, 'top_authors', function ($join) {
                $join->on('authors.id', '=', 'top_authors.author_id');
            })
            ->select('authors.external_id', 'authors.name', 'top_authors.pageviews');

        return $data->get();
    }

    public function topPostTags(TopTagsSearchRequest $request)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        $topTagsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->join('article_tag', 'article_pageviews.article_id', '=', 'article_tag.article_id')
            ->groupBy(['article_tag.tag_id'])
            ->select('article_tag.tag_id', DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews'))
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        $sectionFilters = $this->getFilters($request, 'sections');
        $this->addSectionsCondition($topTagsQuery, $sectionFilters);

        $authorFilters = $this->getFilters($request, 'authors');
        $this->addAuthorsCondition($topTagsQuery, $authorFilters);

        $tagCategoryFilters = $this->getFilters($request, 'tag_categories');
        $this->addTagCategoriesCondition($topTagsQuery, $tagCategoryFilters);

        $contentType = $request->json('content_type');
        if ($contentType) {
            $this->addContentTypeCondition($topTagsQuery, $contentType);
        }

        $data = DB::table('tags')
            ->joinSub($topTagsQuery, 'top_tags', function ($join) {
                $join->on('tags.id', '=', 'top_tags.tag_id');
            })
            ->select('tags.id', 'tags.name', 'tags.external_id', 'top_tags.pageviews');

        return $data->get();
    }

    private function getFilters($request, $filterName): array
    {
        $result = [];
        $filters = $request->json($filterName) ?? [];
        foreach ($filters as $filter) {
            $filterValueType = null;
            $filterValues = null;
            if (isset($filter['external_ids'])) {
                $filterValueType = 'external_id';
                $filterValues = $filter['external_ids'];
            } elseif (isset($filter['names'])) {
                $filterValueType = 'name';
                $filterValues = $filter['names'];
            }
            $result[] = [$filterValueType, $filterValues];
        }

        return $result;
    }

    private function checkQueryType($type): void
    {
        if (!in_array($type, ['external_id', 'name'], true)) {
            throw new \Exception("type '$type' is not one of 'external_id' or 'name' values");
        }
    }

    private function addSectionsCondition(Builder $baseQuery, array $sectionFilters, bool $isArticlesTable = false): void
    {
        $column = $isArticlesTable ? 'articles.id' : 'article_pageviews.article_id';

        foreach ($sectionFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $baseQuery->whereIn($column, function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_section')
                    ->join('sections', 'article_section.section_id', '=', 'sections.id')
                    ->whereIn('sections.' . $type, $values);
            });
        }
    }

    private function addAuthorsCondition(Builder $articlePageviewsQuery, array $authorsFilter, bool $isArticlesTable = false): void
    {
        $column = $isArticlesTable ? 'articles.id' : 'article_pageviews.article_id';

        foreach ($authorsFilter as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn($column, function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_author')
                    ->join('authors', 'article_author.author_id', '=', 'authors.id')
                    ->whereIn('authors.' . $type, $values);
            });
        }
    }

    private function addContentTypeCondition(Builder $baseQuery, string $contentType, bool $isArticlesTable = false): void
    {
        if (!$isArticlesTable && !$this->hasAlreadyJoinedArticlesTable($baseQuery)) {
            $baseQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id');
        }
        $baseQuery->where('articles.content_type', '=', $contentType);
    }

    private function addTagsCondition(Builder $articlePageviewsQuery, array $tagFilters, bool $isArticlesTable = false): void
    {
        $column = $isArticlesTable ? 'articles.id' : 'article_pageviews.article_id';

        foreach ($tagFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn($column, function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_tag')
                    ->join('tags', 'article_tag.tag_id', '=', 'tags.id')
                    ->whereIn('tags.' . $type, $values);
            });
        }
    }

    private function addTagCategoriesCondition(Builder $articlePageviewsQuery, array $tagCategoryFilters, bool $isArticlesTable = false): void
    {
        $column = $isArticlesTable ? 'articles.id' : 'article_pageviews.article_id';

        foreach ($tagCategoryFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn($column, function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_tag as at')
                    ->join('tag_tag_category', 'tag_tag_category.tag_id', '=', 'at.tag_id')
                    ->join('tag_categories', 'tag_categories.id', '=', 'tag_tag_category.tag_category_id')
                    ->whereIn('tag_categories.' . $type, $values);
            });
        }
    }

    private function addPublishedFromCondition(Builder $articlePageviewsQuery, Carbon $publishedFrom, bool $isArticlesTable): void
    {
        if (!$isArticlesTable && !$this->hasAlreadyJoinedArticlesTable($articlePageviewsQuery)) {
            $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id');
        }

        $articlePageviewsQuery->where('articles.published_at', '>=', $publishedFrom);
    }

    private function addPublishedToCondition(Builder $articlePageviewsQuery, Carbon $publishedTo, bool $isArticlesTable): void
    {
        if (!$isArticlesTable && !$this->hasAlreadyJoinedArticlesTable($articlePageviewsQuery)) {
            $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id');
        }

        $articlePageviewsQuery->where('articles.published_at', '<', $publishedTo);
    }

    private function shouldUseArticlesTableAsDataSource(Carbon $timeFrom, ?Carbon $publishedFrom = null, ?Carbon $timeTo = null): bool
    {
        if ($timeTo && $timeTo < Carbon::now()) {
            return false;
        }

        if ($publishedFrom) {
            $publishedFrom = Carbon::parse($publishedFrom);
            if ($publishedFrom >= $timeFrom) {
                return true;
            }
        }

        $cacheKey = "articlePageviewsFirstItemTime";
        $result = Cache::get($cacheKey);
        if (!$result) {
            $data = DB::table('article_pageviews')
                ->orderBy('time_from')
                ->limit(1)
                ->first();

            if (!$data) {
                return true;
            }

            $result = $data->time_from;

            // Cache data for one day
            Cache::put($cacheKey, $result, 86400);
        }

        $timeFromThresholdObject = Carbon::parse($result);

        return $timeFromThresholdObject > $timeFrom;
    }

    private function hasAlreadyJoinedArticlesTable(Builder $builder): bool
    {
        if (!is_array($builder->joins)) {
            return false;
        }

        return in_array('articles', array_map(function ($item) {
            /** @var JoinClause $item */
            return $item->table;
        }, $builder->joins), true);
    }


    private function getPageviewsThreshold(Builder $query, Carbon $timeTo, int $limit): int
    {
        $timeFrom = (clone $timeTo)->subDays(3);

        $data = $query->where('article_pageviews.time_from', '>=', $timeFrom)
            ->limit(1)
            ->offset($limit - 1)
            ->first();

        return $data ? $data->pageviews : 0;
    }
}
