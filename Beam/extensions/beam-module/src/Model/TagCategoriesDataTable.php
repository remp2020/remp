<?php


namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class TagCategoriesDataTable
{
    public function getDataTable(Request $request, DataTables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
        ]);

        $cols = [
            'tag_categories.id',
            'tag_categories.name',
            'COALESCE(tags_count, 0) AS tags_count',
            'COALESCE(articles_count, 0) AS articles_count',
            'COALESCE(conversions_count, 0) AS conversions_count',
            'COALESCE(conversions_amount, 0) AS conversions_amount',

            'COALESCE(pageviews_all, 0) AS pageviews_all',
            'COALESCE(pageviews_not_subscribed, 0) AS pageviews_not_subscribed',
            'COALESCE(pageviews_subscribers, 0) AS pageviews_subscribers',

            'COALESCE(timespent_all, 0) AS timespent_all',
            'COALESCE(timespent_not_subscribed, 0) AS timespent_not_subscribed',
            'COALESCE(timespent_subscribers, 0) AS timespent_subscribers',

            'COALESCE(timespent_all / pageviews_all, 0) AS avg_timespent_all',
            'COALESCE(timespent_not_subscribed / pageviews_not_subscribed, 0) AS avg_timespent_not_subscribed',
            'COALESCE(timespent_subscribers / pageviews_subscribers, 0) AS avg_timespent_subscribers',
        ];

        $tagCategoryTagsQuery = TagTagCategory::selectRaw(implode(',', [
            'tag_category_id',
            'COUNT(DISTINCT tag_tag_category.tag_id) as tags_count'
        ]))
            ->ofSelectedProperty()
            ->groupBy('tag_category_id');

        $tagCategoryArticlesQuery = ArticleTag::selectRaw(implode(',', [
            'tag_category_id',
            'COUNT(DISTINCT articles.id) as articles_count'
        ]))
            ->join('articles', 'article_tag.article_id', '=', 'articles.id')
            ->join('tag_tag_category', 'article_tag.tag_id', '=', 'tag_tag_category.tag_id')
            ->ofSelectedProperty()
            ->groupBy('tag_category_id');

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $tagCategoryArticlesQuery->where('content_type', '=', $request->input('content_type'));
        }

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'tag_tag_category.tag_category_id',
            'count(distinct conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->join('tag_tag_category', 'tag_tag_category.tag_id', '=', 'article_tag.tag_id')
            ->leftJoin('articles', 'article_tag.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy('tag_tag_category.tag_category_id');

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'tag_tag_category.tag_category_id',
            'COALESCE(SUM(pageviews_all), 0) AS pageviews_all',
            'COALESCE(SUM(pageviews_all) - SUM(pageviews_subscribers), 0) AS pageviews_not_subscribed',
            'COALESCE(SUM(pageviews_subscribers), 0) AS pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) AS timespent_all',
            'COALESCE(SUM(timespent_all) - SUM(timespent_subscribers), 0) AS timespent_not_subscribed',
            'COALESCE(SUM(timespent_subscribers), 0) AS timespent_subscribers',
        ]))
            ->leftJoin('article_tag', 'articles.id', '=', 'article_tag.article_id')
            ->join('tag_tag_category', 'tag_tag_category.tag_id', '=', 'article_tag.tag_id')
            ->ofSelectedProperty()
            ->groupBy('tag_tag_category.tag_category_id');

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $pageviewsQuery->where('content_type', '=', $request->input('content_type'));
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
        }

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'));
            $tagCategoryArticlesQuery->where('published_at', '>=', $publishedFrom);
            $conversionsQuery->where('published_at', '>=', $publishedFrom);
            $pageviewsQuery->where('published_at', '>=', $publishedFrom);
        }

        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'));
            $tagCategoryArticlesQuery->where('published_at', '<=', $publishedTo);
            $conversionsQuery->where('published_at', '<=', $publishedTo);
            $pageviewsQuery->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'));
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'));
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $tagCategories = TagCategory::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$tagCategoryArticlesQuery->toSql()}) as aa"), 'tag_categories.id', '=', 'aa.tag_category_id')->addBinding($tagCategoryArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'tag_categories.id', '=', 'c.tag_category_id')->addBinding($conversionsQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'tag_categories.id', '=', 'pv.tag_category_id')->addBinding($pageviewsQuery->getBindings())
            ->leftJoin(DB::raw("({$tagCategoryTagsQuery->toSql()}) as tct"), 'tag_categories.id', '=', 'tct.tag_category_id')->addBinding($tagCategoryTagsQuery->getBindings())
            ->ofSelectedProperty()
            ->groupBy(['tag_categories.name', 'tag_categories.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
                'pageviews_not_subscribed', 'pageviews_subscribers', 'timespent_all', 'timespent_not_subscribed', 'timespent_subscribers']);

        $conversionsQuery = Conversion::selectRaw('count(distinct conversions.id) as count, sum(amount) as sum, currency, tag_tag_category.tag_category_id')
            ->join('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->join('tag_tag_category', 'tag_tag_category.tag_id', '=', 'article_tag.tag_id')
            ->join('articles', 'article_tag.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy(['tag_tag_category.tag_category_id', 'conversions.currency']);

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
        }

        if ($request->input('published_from')) {
            $conversionsQuery->where('published_at', '>=', Carbon::parse($request->input('published_from'), $request->input('tz')));
        }
        if ($request->input('published_to')) {
            $conversionsQuery->where('published_at', '<=', Carbon::parse($request->input('published_to'), $request->input('tz')));
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'));
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'));
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $conversionAmounts = [];
        $conversionCounts = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversionAmounts[$record['tag_category_id']][$record->currency] = $record['sum'];
            if (!isset($conversionCounts[$record['tag_category_id']])) {
                $conversionCounts[$record['tag_category_id']] = 0;
            }
            $conversionCounts[$record['tag_category_id']] += $record['count'];
        }

        /** @var QueryDataTable $datatable */
        $datatable = $datatables->of($tagCategories);
        return $datatable
            ->addColumn('id', function (TagCategory $tagCategory) {
                return $tagCategory->id;
            })
            ->addColumn('name', function (TagCategory $tagCategory) {
                return [
                    'url' => route('tag-categories.show', $tagCategory->id),
                    'text' => $tagCategory->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) use ($request) {
                if ($request->input('search')['value'] === $value) {
                    $query->where(function (Builder $query) use ($value) {
                        $query->where('tag_categories.name', 'like', '%' . $value . '%');
                    });
                } else {
                    $tagCategoryIds = explode(',', $value);
                    $query->where(function (Builder $query) use ($tagCategoryIds) {
                        $query->whereIn('tag_categories.id', $tagCategoryIds);
                    });
                }
            })
            ->addColumn('conversions_count', function (TagCategory $tagCategory) use ($conversionCounts) {
                return $conversionCounts[$tagCategory->id] ?? 0;
            })
            ->addColumn('conversions_amount', function (TagCategory $tagCategory) use ($conversionAmounts) {
                if (!isset($conversionAmounts[$tagCategory->id])) {
                    return 0;
                }
                $amounts = [];
                foreach ($conversionAmounts[$tagCategory->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?: [0];
            })
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->orderColumn('tags_count', 'tags_count $1')
            ->orderColumn('articles_count', 'articles_count $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('pageviews_not_subscribed', 'pageviews_not_subscribed $1')
            ->orderColumn('pageviews_subscribers', 'pageviews_subscribers $1')
            ->orderColumn('avg_timespent_all', 'avg_timespent_all $1')
            ->orderColumn('avg_timespent_not_subscribed', 'avg_timespent_not_subscribed $1')
            ->orderColumn('avg_timespent_subscribers', 'avg_timespent_subscribers $1')
            ->orderColumn('id', 'tag_categories.id $1')
            ->make(true);
    }
}
