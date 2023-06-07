<?php

namespace Remp\BeamModule\Model\Pageviews\Api\v1;

use Remp\BeamModule\Http\Requests\Api\v1\TopArticlesSearchRequest;
use Remp\BeamModule\Http\Requests\Api\v1\TopAuthorsSearchRequest;
use Remp\BeamModule\Http\Requests\Api\v1\TopTagsSearchRequest;
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

        [$sectionValueType, $sectionValues] = $this->getFilter($request, 'sections');
        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($pageviewsQuery, $sectionValueType, $sectionValues);
        }
        [$authorValueType, $authorValues] = $this->getFilter($request, 'authors');
        if ($authorValueType && $authorValues) {
            $this->addAuthorsCondition($pageviewsQuery, $authorValueType, $authorValues);
        }
        [$tagValueType, $tagValues] = $this->getFilter($request, 'tags');
        if ($tagValueType && $tagValues) {
            $this->addTagsCondition($pageviewsQuery, $tagValueType, $tagValues);
        }
        [$tagCategoryValueType, $tagCategoryValues] = $this->getFilter($request, 'tag_categories');
        if ($tagCategoryValueType && $tagCategoryValues) {
            $this->addTagCategoriesCondition($pageviewsQuery, $tagCategoryValueType, $tagCategoryValues);
        }
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

        [$sectionValueType, $sectionValues] = $this->getFilter($request, 'sections');
        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($topAuthorsQuery, $sectionValueType, $sectionValues);
        }
        [$tagValueType, $tagValues] = $this->getFilter($request, 'tags');
        if ($tagValueType && $tagValues) {
            $this->addTagsCondition($topAuthorsQuery, $tagValueType, $tagValues);
        }
        [$tagCategoryValueType, $tagCategoryValues] = $this->getFilter($request, 'tag_categories');
        if ($tagCategoryValueType && $tagCategoryValues) {
            $this->addTagCategoriesCondition($topAuthorsQuery, $tagCategoryValueType, $tagCategoryValues);
        }
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

        [$sectionValueType, $sectionValues] = $this->getFilter($request, 'sections');
        if ($sectionValueType && $sectionValues) {
            $this->addSectionsCondition($topTagsQuery, $sectionValueType, $sectionValues);
        }
        [$authorValueType, $authorValues] = $this->getFilter($request, 'authors');
        if ($authorValueType && $authorValues) {
            $this->addAuthorsCondition($topTagsQuery, $authorValueType, $authorValues);
        }
        [$tagCategoryValueType, $tagCategoryValues] = $this->getFilter($request, 'tag_categories');
        if ($tagCategoryValueType && $tagCategoryValues) {
            $this->addTagCategoriesCondition($topTagsQuery, $tagCategoryValueType, $tagCategoryValues);
        }
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

    private function getFilter($request, $filterName): array
    {
        $filter = $request->json($filterName);
        $filterValueType = null;
        $filterValues = null;
        if (isset($filter['external_id'])) {
            $filterValueType = 'external_id';
            $filterValues = $filter['external_id'];
        } elseif (isset($filter['name'])) {
            $filterValueType = 'name';
            $filterValues = $filter['name'];
        }

        return [$filterValueType, $filterValues];
    }

    private function checkQueryType($type)
    {
        if (!in_array($type, ['external_id', 'name'], true)) {
            throw new \Exception("type '$type' is not one of 'external_id' or 'name' values");
        }
    }

    private function addSectionsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        $this->checkQueryType($type);
        $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
            $query->select('article_id')
                ->from('article_section')
                ->join('sections', 'article_section.section_id', '=', 'sections.id')
                ->whereIn('sections.' . $type, $values);
        });
    }

    private function addAuthorsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        $this->checkQueryType($type);
        $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
            $query->select('article_id')
                ->from('article_author')
                ->join('authors', 'article_author.author_id', '=', 'authors.id')
                ->whereIn('authors.' . $type, $values);
        });
    }

    private function addContentTypeCondition(Builder $articlePageviewsQuery, string $contentType): void
    {
        $articlePageviewsQuery->join('articles', 'article_pageviews.article_id', '=', 'articles.id')
            ->where('articles.content_type', '=', $contentType);
    }

    private function addTagsCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
        $this->checkQueryType($type);
        $articlePageviewsQuery->whereIn('article_pageviews.article_id', function ($query) use ($type, $values) {
            $query->select('article_id')
                ->from('article_tag')
                ->join('tags', 'article_tag.tag_id', '=', 'tags.id')
                ->whereIn('tags.' . $type, $values);
        });
    }

    private function addTagCategoriesCondition(Builder $articlePageviewsQuery, string $type, array $values): void
    {
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
