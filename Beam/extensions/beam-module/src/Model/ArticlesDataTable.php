<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class ArticlesDataTable
{
    private Author $author;

    private Section $section;

    private Tag $tag;

    private TagCategory $tagCategory;

    public function getDataTable(Request $request, DataTables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
        ]);

        // main articles query to fetch list of all articles with related metadata
        $articles = Article::selectRaw(implode(',', [
            "articles.id",
            "articles.title",
            "articles.published_at",
            "articles.url",
            "articles.content_type",
            "articles.pageviews_all",
            "articles.pageviews_signed_in",
            "articles.pageviews_subscribers",
            "articles.timespent_all",
            "articles.timespent_signed_in",
            "articles.timespent_subscribers",
            'timespent_all / pageviews_all as avg_timespent_all',
            'timespent_signed_in / pageviews_signed_in as avg_timespent_signed_in',
            'timespent_subscribers / pageviews_subscribers as avg_timespent_subscribers',
        ]))
            ->with(['authors', 'sections', 'tags'])
            ->ofSelectedProperty()
            ->groupBy(['articles.id', 'articles.title', 'articles.published_at', 'articles.url', "articles.pageviews_all",
                "articles.pageviews_signed_in", "articles.pageviews_subscribers", "articles.timespent_all",
                "articles.timespent_signed_in", "articles.timespent_subscribers", 'avg_timespent_all',
                'avg_timespent_signed_in', 'avg_timespent_subscribers']);

        // filtering query (used as subquery - joins were messing with counts and sums) to fetch matching conversions
        $conversionsFilter = Conversion::distinct()
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->ofselectedProperty();

        if (isset($this->author)) {
            $articles->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
                ->where(['article_author.author_id' => $this->author->id]);
            $conversionsFilter->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
                ->where(['article_author.author_id' => $this->author->id]);
        }
        if (isset($this->section)) {
            $articles->leftJoin('article_section', 'articles.id', '=', 'article_section.article_id')
                ->where(['article_section.section_id' => $this->section->id]);
            $conversionsFilter->leftJoin('article_section', 'articles.id', '=', 'article_section.article_id')
                ->where(['article_section.section_id' => $this->section->id]);
        }
        if (isset($this->tag)) {
            $articles->leftJoin('article_tag as at1', 'articles.id', '=', 'at1.article_id')
                ->where(['at1.tag_id' => $this->tag->id]);
            $conversionsFilter->leftJoin('article_tag as at1', 'articles.id', '=', 'at1.article_id')
                ->where(['at1.tag_id' => $this->tag->id]);
        }
        if (isset($this->tagCategory)) {
            $tags = $this->tagCategory->tags()->pluck('tags.id');
            $articles->leftJoin('article_tag as at2', 'articles.id', '=', 'at2.article_id')
                ->whereIn('at2.tag_id', $tags);
            $conversionsFilter->leftJoin('article_tag as at2', 'articles.id', '=', 'at2.article_id')
                ->whereIn('at2.tag_id', $tags);
        }

        // adding conditions to queries based on request inputs
        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'));
            $articles->where('published_at', '>=', $publishedFrom);
            $conversionsFilter->where('published_at', '>=', $publishedFrom);
        }
        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'));
            $articles->where('published_at', '<=', $publishedTo);
            $conversionsFilter->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'));
            $articles->where('paid_at', '>=', $conversionFrom);
            $conversionsFilter->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'));
            $articles->where('paid_at', '<=', $conversionTo);
            $conversionsFilter->where('paid_at', '<=', $conversionTo);
        }

        // fetch conversions that match the filter
        $matchedConversions = $conversionsFilter->pluck('conversions.id')->toArray();

        // conversion aggregations that are joined to main query (this is required for orderColumn() to work)
        $conversionsJoin = Conversion::selectRaw(implode(',', [
                'count(*) as conversions_count',
                'sum(amount) as conversions_sum',
                'avg(amount) as conversions_avg',
                'article_id'
            ]))
            ->ofSelectedProperty()
            ->groupBy(['article_id']);

        if ($matchedConversions) {
            // intentional sprintf, eloquent was using bindings in wrong order in final query
            $conversionsJoin->whereRaw(sprintf('id IN (%s)', implode(',', $matchedConversions)));
        } else {
            // no conversions matched, don't join anything
            $conversionsJoin->whereRaw('1 = 0');
        }

        $articles->leftJoinSub($conversionsJoin, 'conversions', function ($join) {
            $join->on('articles.id', '=', 'conversions.article_id');
        });
        
        // conversion aggregations for displaying (these are grouped also by the currency)
        $conversionsQuery = Conversion::selectRaw(implode(',', [
                'count(*) as count',
                'sum(amount) as sum',
                'avg(amount) as avg',
                'currency',
                'article_id'
            ]))
            ->whereIn('id', $matchedConversions)
            ->ofSelectedProperty()
            ->groupBy(['article_id', 'currency']);

        $conversionCount = [];
        $conversionSum = [];
        $conversionAvg = [];
        foreach ($conversionsQuery->get() as $record) {
            if (!isset($conversionCount[$record->article_id])) {
                $conversionCount[$record->article_id] = 0;
            }
            $conversionCount[$record->article_id] += $record['count'];
            $conversionSum[$record->article_id][$record->currency] = $record['sum'];
            $conversionAvg[$record->article_id][$record->currency] = $record['avg'];
        }

        /** @var QueryDataTable $datatable */
        $datatable = $datatables->of($articles);
        return $datatable
            ->addColumn('id', function (Article $article) {
                return $article->id;
            })
            ->addColumn('title', function (Article $article) {
                return [
                    'url' => route('articles.show', ['article' => $article->id]),
                    'text' => $article->title,
                ];
            })
            ->addColumn('conversions_count', function (Article $article) use ($conversionCount) {
                return $conversionCount[$article->id] ?? 0;
            })
            ->addColumn('conversions_sum', function (Article $article) use ($conversionSum) {
                if (!isset($conversionSum[$article->id])) {
                    return [0];
                }
                $amounts = null;
                foreach ($conversionSum[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?: [0];
            })
            ->addColumn('conversions_avg', function (Article $article) use ($conversionAvg) {
                if (!isset($conversionAvg[$article->id])) {
                    return [0];
                }
                $amounts = null;
                foreach ($conversionAvg[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?: [0];
            })
            ->filterColumn('title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', "%{$value}%");
            })
            ->filterColumn('content_type', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('articles.content_type', $values);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('article_section')
                    ->select(['article_section.article_id'])
                    ->whereIn('article_section.section_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('authors[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('article_author')
                    ->select(['article_author.article_id'])
                    ->whereIn('article_author.author_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('tags[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('article_tag')
                    ->select(['article_tag.article_id'])
                    ->whereIn('article_tag.tag_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->orderColumn('avg_sum', 'timespent_sum / pageviews_all $1')
            ->orderColumn('avg_timespent_signed_in', 'avg_timespent_signed_in $1')
            ->orderColumn('avg_timespent_all', 'avg_timespent_all $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('timespent_sum', 'timespent_sum $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_sum', 'conversions_sum $1')
            ->orderColumn('conversions_avg', 'conversions_avg $1')
            ->orderColumn('id', 'articles.id $1')
            ->make(true);
    }

    public function setAuthor(Author $author): void
    {
        $this->author = $author;
    }

    public function setSection(Section $section): void
    {
        $this->section = $section;
    }

    public function setTag(Tag $tag): void
    {
        $this->tag = $tag;
    }

    public function setTagCategory(TagCategory $tagCategory): void
    {
        $this->tagCategory = $tagCategory;
    }
}
