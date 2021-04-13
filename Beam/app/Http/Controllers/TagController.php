<?php

namespace App\Http\Controllers;

use App\Article;
use App\ArticleTag;
use App\Author;
use App\Conversion;
use App\Http\Request;
use App\Http\Requests\TopSearchRequest;
use App\Http\Resources\TagResource;
use App\Model\Pageviews\TopSearch;
use App\Model\Tag;
use App\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Html;

class TagController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('tags.index', [
                'tags' => Tag::all()->pluck('name', 'id'),
                'contentTypes' => array_merge(
                    ['all'],
                    Article::groupBy('content_type')->pluck('content_type')->toArray()
                ),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
                'contentType' => $request->input('content_type', 'all'),
            ]),
            'json' => TagResource::collection(Tag::paginate()),
        ]);
    }

    public function show(Tag $tag, Request $request)
    {
        return response()->format([
            'html' => view('tags.show', [
                'tag' => $tag,
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::all()->pluck('name', 'id'),
                'authors' => Author::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => new TagResource($tag),
        ]);
    }

    public function dtTags(Request $request, DataTables $datatables)
    {
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

        $tagArticlesQuery = ArticleTag::selectRaw(implode(',', [
            'tag_id',
            'COUNT(*) as articles_count'
        ]))
            ->leftJoin('articles', 'article_tag.article_id', '=', 'articles.id')
            ->groupBy('tag_id');

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $tagArticlesQuery->where('content_type', '=', $request->input('content_type'));
        }

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'tag_id',
            'count(distinct conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->leftJoin('articles', 'article_tag.article_id', '=', 'articles.id')
            ->groupBy('tag_id');

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'tag_id',
            'COALESCE(SUM(pageviews_all), 0) AS pageviews_all',
            'COALESCE(SUM(pageviews_all) - SUM(pageviews_subscribers), 0) AS pageviews_not_subscribed',
            'COALESCE(SUM(pageviews_subscribers), 0) AS pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) AS timespent_all',
            'COALESCE(SUM(timespent_all) - SUM(timespent_subscribers), 0) AS timespent_not_subscribed',
            'COALESCE(SUM(timespent_subscribers), 0) AS timespent_subscribers',
        ]))
            ->leftJoin('article_tag', 'articles.id', '=', 'article_tag.article_id')
            ->groupBy('tag_id');

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $pageviewsQuery->where('content_type', '=', $request->input('content_type'));
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
        }

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'));
            $tagArticlesQuery->where('published_at', '>=', $publishedFrom);
            $conversionsQuery->where('published_at', '>=', $publishedFrom);
            $pageviewsQuery->where('published_at', '>=', $publishedFrom);
        }

        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'));
            $tagArticlesQuery->where('published_at', '<=', $publishedTo);
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
            ->leftJoin(DB::raw("({$tagArticlesQuery->toSql()}) as aa"), 'tags.id', '=', 'aa.tag_id')->addBinding($tagArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'tags.id', '=', 'c.tag_id')->addBinding($conversionsQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'tags.id', '=', 'pv.tag_id')->addBinding($pageviewsQuery->getBindings())
            ->groupBy(['tags.name', 'tags.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
                'pageviews_not_subscribed', 'pageviews_subscribers', 'timespent_all', 'timespent_not_subscribed', 'timespent_subscribers']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('count(distinct conversions.id) as count, sum(amount) as sum, currency, article_tag.tag_id')
            ->join('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->join('articles', 'article_tag.article_id', '=', 'articles.id')
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
            $conversionAmounts[$record->tag_id][$record->currency] = $record->sum;
            $conversionCounts[$record->tag_id] = $record->count;
        }

        return $datatables->of($tags)
            ->addColumn('name', function (Tag $tag) {
                return Html::linkRoute('tags.show', $tag->name, $tag);
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $tagIds = explode(',', $value);
                $query->where(function (Builder $query) use ($tagIds, $value) {
                    $query->where('tags.name', 'like', '%' . $value . '%')
                        ->orWhereIn('tags.id', $tagIds);
                });
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
                return $amounts ?? [0];
            })
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->make(true);
    }

    public function dtArticles(Tag $tag, Request $request, Datatables $datatables)
    {
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
            ->join('article_tag', 'articles.id', '=', 'article_tag.article_id')
            ->leftJoin('article_section', 'articles.id', '=', 'article_section.article_id')
            ->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
            ->where([
                'article_tag.tag_id' => $tag->id
            ])
            ->groupBy(['articles.id', 'articles.title', 'articles.published_at', 'articles.url', "articles.pageviews_all",
                "articles.pageviews_signed_in", "articles.pageviews_subscribers", "articles.timespent_all",
                "articles.timespent_signed_in", "articles.timespent_subscribers", 'avg_timespent_all',
                'avg_timespent_signed_in', 'avg_timespent_subscribers']);

        // filtering query (used as subquery - joins were messing with counts and sums) to fetch matching conversions
        $conversionsFilter = \DB::table('conversions')
            ->distinct()
            ->join('article_tag', 'conversions.article_id', '=', 'article_tag.article_id')
            ->join('articles', 'articles.id', '=', 'article_tag.article_id')
            ->where([
                'article_tag.tag_id' => $tag->id
            ]);
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
        $conversionsJoin = \DB::table('conversions')
            ->selectRaw(implode(',', [
                'count(*) as conversions_count',
                'sum(amount) as conversions_sum',
                'avg(amount) as conversions_avg',
                'article_id'
            ]))
            ->groupBy(['article_id']);

        if ($matchedConversions) {
            // intentional sprintf, eloquent was using bindings in wrong order in final query
            $conversionsJoin->whereRaw(sprintf('id IN (%s)', implode(',', $matchedConversions)));
        } else {
            // no conversions matched, don't join anything
            $conversionsJoin->whereRaw('1 = 0');
        }

        $articles->leftJoin(DB::raw("({$conversionsJoin->toSql()}) as conversions"), 'articles.id', '=', 'conversions.article_id')
            ->addBinding($conversionsJoin->getBindings());

        // conversion aggregations for displaying (these are grouped also by the currency)
        $conversionsQuery = \DB::table('conversions')
            ->selectRaw(implode(',', [
                'count(*) as count',
                'sum(amount) as sum',
                'avg(amount) as avg',
                'currency',
                'article_id'
            ]))
            ->whereIn('id', $matchedConversions)
            ->groupBy(['article_id', 'currency']);

        $conversionCount = [];
        $conversionSum = [];
        $conversionAvg = [];
        foreach ($conversionsQuery->get() as $record) {
            if (!isset($conversionCount[$record->article_id])) {
                $conversionCount[$record->article_id] = 0;
            }
            $conversionCount[$record->article_id] += $record->count;
            $conversionSum[$record->article_id][$record->currency] = $record->sum;
            $conversionAvg[$record->article_id][$record->currency] = $record->avg;
        }

        // final datatable
        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return Html::link(route('articles.show', ['article' => $article->id]), $article->title);
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
                return $amounts ?? [0];
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
                return $amounts ?? [0];
            })
            ->filterColumn('content_type', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('articles.content_type', $values);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('sections')
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.author_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->filterColumn('authors[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('authors')
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $articleIds = $filterQuery->pluck('articles.id')->toArray();
                $query->whereIn('articles.id', $articleIds);
            })
            ->orderColumn('avg_sum', 'timespent_sum / pageviews_all $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('timespent_sum', 'timespent_sum $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_sum', 'conversions_sum $1')
            ->orderColumn('conversions_avg', 'conversions_avg $1')
            ->make(true);
    }

    public function topTags(TopSearchRequest $request, TopSearch $topSearch)
    {
        $limit = $request->json('limit');
        $timeFrom = Carbon::parse($request->json('from'));

        $sections = $request->json('sections');
        $sectionValueType = null;
        $sectionValues = null;
        if (isset($sections['external_id'])) {
            $sectionValueType = 'external_id';
            $sectionValues = $sections['external_id'];
        } elseif (isset($sections['name'])) {
            $sectionValueType = 'name';
            $sectionValues = $sections['name'];
        }

        $authors = $request->json('authors');
        $authorValueType = null;
        $authorValues = null;
        if (isset($authors['external_id'])) {
            $authorValueType = 'external_id';
            $authorValues = $authors['external_id'];
        } elseif (isset($authors['name'])) {
            $authorValueType = 'name';
            $authorValues = $authors['name'];
        }

        $contentType = $request->json('content_type');

        return response()->json($topSearch->topPostTags(
            $timeFrom,
            $limit,
            $sectionValueType,
            $sectionValues,
            $authorValueType,
            $authorValues,
            $contentType
        ));
    }
}
