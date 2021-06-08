<?php

namespace App\Model\Pageviews\Api\v2;

use App\Http\Requests\Api\v2\TopArticlesSearchRequest;
use App\Http\Requests\Api\v2\TopAuthorsSearchRequest;
use App\Http\Requests\Api\v2\TopTagsSearchRequest;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class TopSearch
{
    public function topArticles(TopArticlesSearchRequest $request)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        $pageviewsQuery = DB::table('article_pageviews')
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->groupBy('article_pageviews.article_id')
            ->select(
                'article_pageviews.article_id',
                DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews')
            )
            ->orderBy('pageviews', 'DESC')
            ->limit($limit);

        $sectionFilters = $this->getFilters($request, 'sections');
        $this->addSectionsCondition($pageviewsQuery, $sectionFilters);

        $authorFilters = $this->getFilters($request, 'authors');
        $this->addAuthorsCondition($pageviewsQuery, $authorFilters);

        $tagFilters = $this->getFilters($request, 'tags');
        $this->addTagsCondition($pageviewsQuery, $tagFilters);

        $tagCategoryFilters = $this->getFilters($request, 'tag_categories');
        $this->addTagCategoriesCondition($pageviewsQuery, $tagCategoryFilters);

        $contentType = $request->json('content_type');
        if ($contentType) {
            $this->addContentTypeCondition($pageviewsQuery, $contentType);
        }

        $data = DB::table('articles')
            ->joinSub($pageviewsQuery, 'top_articles_by_pageviews', function ($join) {
                $join->on('articles.id', '=', 'top_articles_by_pageviews.article_id');
            })
            ->select('articles.id', 'articles.external_id', 'top_articles_by_pageviews.pageviews');

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

    private function addSectionsCondition(Builder $articlePageviewsQuery, array $sectionFilters): void
    {
        foreach ($sectionFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_section')
                    ->join('sections', 'article_section.section_id', '=', 'sections.id')
                    ->whereIn('sections.' . $type, $values);
            });
        }
    }

    private function addAuthorsCondition(Builder $articlePageviewsQuery, array $authorsFilter): void
    {
        foreach ($authorsFilter as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_author')
                    ->join('authors', 'article_author.author_id', '=', 'authors.id')
                    ->whereIn('authors.' . $type, $values);
            });
        }
    }

    private function addContentTypeCondition(Builder $articlePageviewsQuery, string $contentType): void
    {
        $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id')
            ->where('articles.content_type', '=', $contentType);
    }

    private function addTagsCondition(Builder $articlePageviewsQuery, array $tagFilters): void
    {
        foreach ($tagFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_tag')
                    ->join('tags', 'article_tag.tag_id', '=', 'tags.id')
                    ->whereIn('tags.' . $type, $values);
            });
        }
    }

    private function addTagCategoriesCondition(Builder $articlePageviewsQuery, array $tagCategoryFilters): void
    {
        foreach ($tagCategoryFilters as [$type, $values]) {
            $this->checkQueryType($type);
            $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
                $query->select('article_id')
                    ->from('article_tag as at')
                    ->join('tag_tag_category', 'tag_tag_category.tag_id', '=', 'at.tag_id')
                    ->join('tag_categories', 'tag_categories.id', '=', 'tag_tag_category.tag_category_id')
                    ->whereIn('tag_categories.' . $type, $values);
            });
        }
    }
}
