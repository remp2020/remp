<?php

namespace App\Http\Controllers;

use App\Article;
use App\ArticleAuthor;
use App\ArticlePageviews;
use App\ArticleTimespent;
use App\Author;
use App\Conversion;
use App\Http\Resources\AuthorResource;
use App\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Html;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('authors.index', [
                'authors' => Author::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'now - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'now - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => AuthorResource::collection(Author::paginate()),
        ]);
    }

    public function show(Author $author, Request $request)
    {
        return response()->format([
            'html' => view('authors.show', [
                'author' => $author,
                'sections' => Section::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'now - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'now - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => new AuthorResource($author),
        ]);
    }

    public function dtAuthors(Request $request, Datatables $datatables)
    {
        $cols = [
            'authors.id',
            'authors.name',
            'COALESCE(articles_count, 0) AS articles_count',
            'COALESCE(conversions_count, 0) AS conversions_count',
            'COALESCE(conversions_amount, 0) AS conversions_amount',
            'COALESCE(pageviews_all, 0) AS pageviews_all',
            'COALESCE(pageviews_signed_in, 0) AS pageviews_signed_in',
            'COALESCE(pageviews_subscribers, 0) AS pageviews_subscribers',
            'COALESCE(timespent_all, 0) AS timespent_all',
            'COALESCE(timespent_signed_in, 0) AS timespent_signed_in',
            'COALESCE(timespent_subscribers, 0) AS timespent_subscribers',
            'COALESCE(timespent_all / pageviews_all, 0) AS avg_timespent_all',
            'COALESCE(timespent_signed_in / pageviews_signed_in, 0) AS avg_timespent_signed_in',
            'COALESCE(timespent_subscribers / pageviews_subscribers, 0) AS avg_timespent_subscribers',
        ];

        $authorArticlesQuery = ArticleAuthor::selectRaw(implode(',', [
            'author_id',
            'COUNT(*) as articles_count'
        ]))
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'author_id',
            'count(distinct conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'author_id',
            'COALESCE(SUM(pageviews_all), 0) AS pageviews_all',
            'COALESCE(SUM(pageviews_signed_in), 0) AS pageviews_signed_in',
            'COALESCE(SUM(pageviews_subscribers), 0) AS pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) AS timespent_all',
            'COALESCE(SUM(timespent_signed_in), 0) AS timespent_signed_in',
            'COALESCE(SUM(timespent_subscribers), 0) AS timespent_subscribers',
        ]))
            ->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
            ->groupBy('author_id');

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'))->tz('UTC');
            $authorArticlesQuery->where('published_at', '>=', $publishedFrom);
            $conversionsQuery->where('published_at', '>=', $publishedFrom);
            $pageviewsQuery->where('published_at', '>=', $publishedFrom);
        }

        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'))->tz('UTC');
            $authorArticlesQuery->where('published_at', '<=', $publishedTo);
            $conversionsQuery->where('published_at', '<=', $publishedTo);
            $pageviewsQuery->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'))->tz('UTC');
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'))->tz('UTC');
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $authors = Author::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$authorArticlesQuery->toSql()}) as aa"), 'authors.id', '=', 'aa.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'authors.id', '=', 'c.author_id')->addBinding($conversionsQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'authors.id', '=', 'pv.author_id')->addBinding($pageviewsQuery->getBindings())
            ->groupBy(['authors.name', 'authors.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
                'pageviews_signed_in', 'pageviews_subscribers', 'timespent_all', 'timespent_signed_in', 'timespent_subscribers']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('count(distinct conversions.id) as count, sum(amount) as sum, currency, article_author.author_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy(['article_author.author_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $conversionsQuery->where('published_at', '>=', Carbon::parse($request->input('published_from'), $request->input('tz'))->tz('UTC'));
        }
        if ($request->input('published_to')) {
            $conversionsQuery->where('published_at', '<=', Carbon::parse($request->input('published_to'), $request->input('tz'))->tz('UTC'));
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'))->tz('UTC');http://beam.remp.press/authors?published_from=now%20-%202%20years&published_to=now&conversion_from=first%20day%20of%20this%20month&conversion_to=first%20day%20of%20next%20month%20-%201%20sec&tz=Europe%2FBratislava#
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'))->tz('UTC');
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $conversionAmounts = [];
        $conversionCounts = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversionAmounts[$record->author_id][$record->currency] = $record->sum;
            $conversionCounts[$record->author_id] = $record->count;
        }

        return $datatables->of($authors)
            ->addColumn('name', function (Author $author) {
                return Html::linkRoute('authors.show', $author->name, $author);
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $authorIds = explode(',', $value);
                $query->where(function (Builder $query) use ($authorIds, $value) {
                    $query->where('authors.name', 'like', '%' . $value . '%')
                        ->orWhereIn('authors.id', $authorIds);
                });
            })
            ->addColumn('conversions_count', function (Author $author) use ($conversionCounts) {
                return $conversionCounts[$author->id] ?? 0;
            })
            ->addColumn('conversions_amount', function (Author $author) use ($conversionAmounts) {
                if (!isset($conversionAmounts[$author->id])) {
                    return 0;
                }
                $amounts = [];
                foreach ($conversionAmounts[$author->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?? [0];
            })
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->make(true);
    }

    public function dtArticles(Author $author, Request $request, Datatables $datatables)
    {
        $articles = Article::selectRaw(implode(',', [
            "articles.id",
            "articles.title",
            "articles.published_at",
            "articles.url",
            "articles.pageviews_all",
            "articles.pageviews_signed_in",
            "articles.pageviews_subscribers",
            "articles.timespent_all",
            "articles.timespent_signed_in",
            "articles.timespent_subscribers",
            "count(conversions.id) as conversions_count",
            "coalesce(sum(conversions.amount), 0) as conversions_sum",
            "avg(conversions.amount) as conversions_avg",
            'timespent_all / pageviews_all as avg_timespent_all',
            'timespent_signed_in / pageviews_signed_in as avg_timespent_signed_in',
            'timespent_subscribers / pageviews_subscribers as avg_timespent_subscribers',
        ]))
            ->with(['authors', 'sections'])
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id')
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id')
            ->where([
                'article_author.author_id' => $author->id
            ])
            ->groupBy(['articles.id', 'articles.title', 'articles.published_at', 'articles.url', "articles.pageviews_all",
                "articles.pageviews_signed_in", "articles.pageviews_subscribers", "articles.timespent_all",
                "articles.timespent_signed_in", "articles.timespent_subscribers", 'avg_timespent_all',
                'avg_timespent_signed_in', 'avg_timespent_subscribers']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('sum(amount) as sum, avg(amount) as avg, currency, article_author.article_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'articles.id', '=', 'article_author.article_id')
            ->groupBy(['article_author.article_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'))->tz('UTC');
            $articles->where('published_at', '>=', $publishedFrom);
            $conversionsQuery->where('published_at', '>=', $publishedFrom);
        }
        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'))->tz('UTC');
            $articles->where('published_at', '<=', $publishedTo);
            $conversionsQuery->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'))->tz('UTC');
            $articles->where('paid_at', '>=', $conversionFrom);
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'))->tz('UTC');
            $articles->where('paid_at', '<=', $conversionTo);
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $conversions = [];
        $averageConversions = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversions[$record->article_id][$record->currency] = $record->sum;
            $averageConversions[$record->article_id][$record->currency] = $record->avg;
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return Html::link(route('articles.show', ['article' => $article->id]), $article->title);
            })
            ->addColumn('conversions_sum', function (Article $article) use ($conversions) {
                if (!isset($conversions[$article->id])) {
                    return [0];
                }
                $amounts = null;
                foreach ($conversions[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?? [0];
            })
            ->addColumn('conversions_avg', function (Article $article) use ($averageConversions) {
                if (!isset($averageConversions[$article->id])) {
                    return [0];
                }
                $amounts = null;
                foreach ($averageConversions[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?? [0];
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->orderColumn('avg_sum', 'timespent_sum / pageviews_all $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('timespent_sum', 'timespent_sum $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_sum', 'conversions_sum $1')
            ->orderColumn('conversions_avg', 'conversions_avg $1')
            ->make(true);
    }
}
