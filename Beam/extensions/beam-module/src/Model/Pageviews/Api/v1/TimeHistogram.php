<?php

namespace Remp\BeamModule\Model\Pageviews\Api\v1;

use Remp\BeamModule\Http\Requests\Api\v1\PageviewsTimeHistogramRequest;
use DateInterval;
use DatePeriod;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class TimeHistogram
{

    public function getTimeHistogram(PageviewsTimeHistogramRequest $request)
    {
        $timeFrom = Carbon::parse($request->json('from'));
        $timeTo = Carbon::parse($request->json('to'));

        $pageviewsQuery = DB::table('article_pageviews')
            ->select(
                DB::raw('DATE(article_pageviews.time_from) as date'),
                DB::raw('CAST(SUM(article_pageviews.sum) AS UNSIGNED) as pageviews')
            )
            ->where('article_pageviews.time_from', '>=', $timeFrom)
            ->where('article_pageviews.time_from', '<', $timeTo)
            ->groupBy('date');

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

        $data = $pageviewsQuery->pluck('pageviews', 'date');
        return $this->fillMissingDates($timeFrom, $timeTo, $data);
    }

    private function fillMissingDates($from, $to, $data)
    {
        $result = [];
        $period = new DatePeriod($from, new DateInterval('P1D'), $to);

        foreach ($period as $date) {
            $date = $date->format('Y-m-d');
            $result[] = ['date' => $date, 'pageviews' => $data[$date] ?? 0];
        }

        return $result;
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
