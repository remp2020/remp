<?php


namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class TagsDataTable
{
    private TagCategory $tagCategory;

    public function getDataTable(Request $request, DataTables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
        ]);

        $cols = [
            'tags.id',
            'tags.name',
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

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'tag_id',
            'count(distinct conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->leftJoin('articles', 'article_tag.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy('tag_id');

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'tag_id',
            'COALESCE(SUM(pageviews_all), 0) AS pageviews_all',
            'COALESCE(SUM(pageviews_all) - SUM(pageviews_subscribers), 0) AS pageviews_not_subscribed',
            'COALESCE(SUM(pageviews_subscribers), 0) AS pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) AS timespent_all',
            'COALESCE(SUM(timespent_all) - SUM(timespent_subscribers), 0) AS timespent_not_subscribed',
            'COALESCE(SUM(timespent_subscribers), 0) AS timespent_subscribers',
            'COUNT(*) as articles_count',
        ]))
            ->leftJoin('article_tag', 'articles.id', '=', 'article_tag.article_id')
            ->ofSelectedProperty()
            ->groupBy('tag_id');

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $pageviewsQuery->where('content_type', '=', $request->input('content_type'));
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
        }

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'));
            $conversionsQuery->where('published_at', '>=', $publishedFrom);
            $pageviewsQuery->where('published_at', '>=', $publishedFrom);
        }

        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'));
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

        $tags = Tag::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'tags.id', '=', 'c.tag_id')->addBinding($conversionsQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'tags.id', '=', 'pv.tag_id')->addBinding($pageviewsQuery->getBindings())
            ->ofSelectedProperty()
            ->groupBy(['tags.name', 'tags.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
                'pageviews_not_subscribed', 'pageviews_subscribers', 'timespent_all', 'timespent_not_subscribed', 'timespent_subscribers']);

        if (isset($this->tagCategory)) {
            $tags->whereIn('tags.id', $this->tagCategory->tags()->pluck('tags.id'));
        }

        $conversionsQuery = Conversion::selectRaw('count(distinct conversions.id) as count, sum(amount) as sum, currency, article_tag.tag_id')
            ->join('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->join('articles', 'article_tag.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy(['article_tag.tag_id', 'conversions.currency']);

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
            $conversionAmounts[$record['tag_id']][$record->currency] = $record['sum'];
            if (!isset($conversionCounts[$record['tag_id']])) {
                $conversionCounts[$record['tag_id']] = 0;
            }
            $conversionCounts[$record['tag_id']] += $record['count'];
        }

        /** @var QueryDataTable $datatable */
        $datatable = $datatables->of($tags);
        return $datatable
            ->addColumn('id', function (Tag $tag) {
                return $tag->id;
            })
            ->addColumn('name', function (Tag $tag) {
                return [
                    'url' => route('tags.show', ['tag' => $tag]),
                    'text' => $tag->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) use ($request) {
                if ($request->input('search')['value'] === $value) {
                    $query->where(function (Builder $query) use ($value) {
                        $query->where('tags.name', 'like', '%' . $value . '%');
                    });
                } else {
                    $tagIds = explode(',', $value);
                    $query->where(function (Builder $query) use ($tagIds) {
                        $query->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->addColumn('conversions_count', function (Tag $tag) use ($conversionCounts) {
                return $conversionCounts[$tag->id] ?? 0;
            })
            ->addColumn('conversions_amount', function (Tag $tag) use ($conversionAmounts) {
                if (!isset($conversionAmounts[$tag->id])) {
                    return 0;
                }
                $amounts = [];
                foreach ($conversionAmounts[$tag->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?: [0];
            })
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->orderColumn('articles_count', 'articles_count $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('pageviews_not_subscribed', 'pageviews_not_subscribed $1')
            ->orderColumn('pageviews_subscribers', 'pageviews_subscribers $1')
            ->orderColumn('avg_timespent_all', 'avg_timespent_all $1')
            ->orderColumn('avg_timespent_not_subscribed', 'avg_timespent_not_subscribed $1')
            ->orderColumn('avg_timespent_subscribers', 'avg_timespent_subscribers $1')
            ->orderColumn('id', 'tags.id $1')
            ->setTotalRecords(PHP_INT_MAX)
            ->setFilteredRecords(PHP_INT_MAX)
            ->make(true);
    }

    public function setTagCategory(TagCategory $tagCategory): void
    {
        $this->tagCategory = $tagCategory;
    }
}
