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
use HTML;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('authors.index', [
                'authors' => Author::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', Carbon::now()->subMonth()),
                'publishedTo' => $request->input('published_to', Carbon::now()),
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
                'publishedFrom' => $request->input('published_from', Carbon::now()->subMonth()),
                'publishedTo' => $request->input('published_to', Carbon::now()),
            ]),
            'json' => new AuthorResource($author),
        ]);
    }

    public function dtAuthors(Request $request, Datatables $datatables)
    {
        $cols = [
            'authors.id',
            'authors.name',
            'articles_count',
            'conversions_count',
            'conversions_amount',
            'pageviews_count',
            'pageviews_timespent / pageviews_count as avg_timespent',
        ];

        $authorArticlesQuery = ArticleAuthor::selectRaw(implode(',', [
            'author_id',
            'COUNT(*) as articles_count'
        ]))
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'author_id',
            'count(conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        $pageviewsQuery = ArticlePageviews::selectRaw(implode(',', [
            'author_id',
            'COALESCE(SUM(sum), 0) as pageviews_count',
        ]))
            ->leftJoin('article_author', 'article_pageviews.article_id', '=', 'article_author.article_id')
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        $timespentQuery = ArticleTimespent::selectRaw(implode(',', [
            'author_id',
            'COALESCE(SUM(sum), 0) as pageviews_timespent',
        ]))
            ->leftJoin('article_author', 'article_timespents.article_id', '=', 'article_author.article_id')
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy('author_id');

        if ($request->input('published_from')) {
            $authorArticlesQuery->whereDate('published_at', '>=', $request->input('published_from'));
            $conversionsQuery->whereDate('published_at', '>=', $request->input('published_from'));
            $pageviewsQuery->whereDate('published_at', '>=', $request->input('published_from'));
            $timespentQuery->whereDate('published_at', '>=', $request->input('published_from'));
        }

        if ($request->input('published_to')) {
            $authorArticlesQuery->whereDate('published_at', '<=', $request->input('published_to'));
            $conversionsQuery->whereDate('published_at', '<=', $request->input('published_to'));
            $pageviewsQuery->whereDate('published_at', '<=', $request->input('published_to'));
            $timespentQuery->whereDate('published_at', '<=', $request->input('published_to'));
        }

        $authors = Author::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$authorArticlesQuery->toSql()}) as aa"), 'authors.id', '=', 'aa.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'authors.id', '=', 'c.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'authors.id', '=', 'pv.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$timespentQuery->toSql()}) as ts"), 'authors.id', '=', 'ts.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->groupBy(['authors.name', 'authors.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_count', 'pageviews_timespent']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('sum(amount) as sum, currency, article_author.author_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy(['article_author.author_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $conversionsQuery->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $conversionsQuery->where('published_at', '<=', $request->input('published_to'));
        }

        $conversions = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversions[$record->author_id][$record->currency] = $record->sum;
        }

        return $datatables->of($authors)
            ->filterColumn('name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('authors.id', $values);
            })
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->addColumn('conversions_amount', function (Author $author) use ($conversions) {
                if (!isset($conversions[$author->id])) {
                    return 0;
                }
                $amounts = [];
                foreach ($conversions[$author->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?? [0];
            })
            ->addColumn('name', function (Author $author) {
                return HTML::linkRoute('authors.show', $author->name, $author);
            })
            ->make(true);
    }

    public function dtArticles(Author $author, Request $request, Datatables $datatables)
    {
        $articles = Article::selectRaw(implode(',', [
                "articles.id",
                "articles.title",
                "articles.published_at",
                "articles.url",
                "articles.pageview_sum",
                "articles.timespent_sum",
                "count(conversions.id) as conversions_count",
                "coalesce(sum(conversions.amount), 0) as conversions_sum",
                "avg(conversions.amount) as conversions_avg",
            ]))
            ->with(['authors', 'sections'])
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id')
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id')
            ->where([
                'article_author.author_id' => $author->id
            ])
            ->groupBy(['articles.id', 'articles.title', 'articles.published_at', 'articles.url']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('sum(amount) as sum, avg(amount) as avg, currency, article_author.article_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'articles.id', '=', 'article_author.article_id')
            ->groupBy(['article_author.article_id', 'conversions.currency']);

        $averages = \DB::table('articles')
            ->selectRaw(implode(',', [
                'avg(nullif(pageview_sum, 0)) as pageview_avg',
                'avg(nullif(timespent_sum, 0)) as timespent_avg',
                'avg(nullif(timespent_sum / pageview_sum, 0)) as average_avg',
                'sum(case when conversions.id is not null then 1 else 0 end) / count(distinct articles.id) as conversion_count_avg',
            ]))
            ->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
            ->leftJoin('conversions', 'conversions.article_id', '=', 'articles.id')
            ->where([
                'article_author.author_id' => $author->id,
            ])
            ->first();

        $conversionAverages = \DB::table('conversions')
            ->selectRaw(implode(',', [
                'currency',
                'sum(conversions.amount) / count(distinct conversions.article_id) as conversion_sum_avg',
                'avg(conversions.amount) as conversion_avg',
            ]))
            ->leftJoin('article_author', 'article_author.article_id', '=', 'conversions.article_id')
            ->where([
                'article_author.author_id' => $author->id,
            ])
            ->groupBy(['conversions.currency'])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => $item];
            });

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', $request->input('published_from'));
            $conversionsQuery->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', $request->input('published_to'));
            $conversionsQuery->where('published_at', '<=', $request->input('published_to'));
        }

        $conversions = [];
        $averageConversions = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversions[$record->article_id][$record->currency] = $record->sum;
            $averageConversions[$record->article_id][$record->currency] = $record->avg;
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::link($article->url, $article->title);
            })
            ->addColumn('pageview_sum', function (Article $article) use ($averages) {
                $arr = [$article->pageview_sum];
                if ($article->pageview_sum > 0) {
                    $arr[] = $averages->pageview_avg;
                }
                return $arr;
            })
            ->addColumn('timespent_sum', function (Article $article) use ($averages) {
                $arr = [$article->timespent_sum];
                if ($article->timespent_sum > 0) {
                    $arr[] = $averages->timespent_avg;
                }
                return $arr;
            })
            ->addColumn('avg_sum', function (Article $article) use ($averages) {
                if (!$article->timespent_sum || !$article->pageview_sum) {
                    return [0];
                }
                return [round($article->timespent_sum / $article->pageview_sum), $averages->average_avg];
            })
            ->addColumn('conversions_count', function (Article $article) use ($averages) {
                if (!$article->conversions_count) {
                    return [0];
                }
                return [$article->conversions_count, $averages->conversion_count_avg];
            })
            ->addColumn('conversions_sum', function (Article $article) use ($conversions, $conversionAverages) {
                if (!isset($conversions[$article->id])) {
                    return [[0]];
                }
                $amounts = null;
                foreach ($conversions[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = ["{$c} {$currency}", $conversionAverages->get($currency)->conversion_sum_avg];
                }
                return $amounts ?? [[0]];
            })
            ->addColumn('conversions_avg', function (Article $article) use ($averageConversions, $conversionAverages) {
                if (!isset($averageConversions[$article->id])) {
                    return [[0]];
                }
                $amounts = null;
                foreach ($averageConversions[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = ["{$c} {$currency}", $conversionAverages->get($currency)->conversion_avg];
                }
                return $amounts ?? [[0]];
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->orderColumn('avg_sum', 'timespent_sum / pageview_sum $1')
            ->orderColumn('pageview_sum', 'pageview_sum $1')
            ->orderColumn('timespent_sum', 'timespent_sum $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_sum', 'conversions_sum $1')
            ->orderColumn('conversions_avg', 'conversions_avg $1')
            ->make(true);
    }
}
