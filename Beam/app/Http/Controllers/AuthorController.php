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
            'pageviews_all',
            'pageviews_signed_in',
            'pageviews_subscribers',
			'timespent_all',
			'timespent_signed_in',
			'timespent_subscribers',
            'timespent_all / pageviews_all as avg_timespent_all',
            'timespent_signed_in / pageviews_signed_in as avg_timespent_signed_in',
            'timespent_subscribers / pageviews_subscribers as avg_timespent_subscribers',
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

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'author_id',
            'COALESCE(SUM(pageviews_all), 0) as pageviews_all',
            'COALESCE(SUM(pageviews_signed_in), 0) as pageviews_signed_in',
            'COALESCE(SUM(pageviews_subscribers), 0) as pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) as timespent_all',
            'COALESCE(SUM(timespent_signed_in), 0) as timespent_signed_in',
            'COALESCE(SUM(timespent_subscribers), 0) as timespent_subscribers',
        ]))
            ->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
            ->groupBy('author_id');

        if ($request->input('published_from')) {
            $authorArticlesQuery->whereDate('published_at', '>=', $request->input('published_from'));
            $conversionsQuery->whereDate('published_at', '>=', $request->input('published_from'));
            $pageviewsQuery->whereDate('published_at', '>=', $request->input('published_from'));
        }

        if ($request->input('published_to')) {
            $authorArticlesQuery->whereDate('published_at', '<=', $request->input('published_to'));
            $conversionsQuery->whereDate('published_at', '<=', $request->input('published_to'));
            $pageviewsQuery->whereDate('published_at', '<=', $request->input('published_to'));
        }

        $authors = Author::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$authorArticlesQuery->toSql()}) as aa"), 'authors.id', '=', 'aa.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'authors.id', '=', 'c.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'authors.id', '=', 'pv.author_id')->addBinding($authorArticlesQuery->getBindings())
            ->groupBy(['authors.name', 'authors.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
				'pageviews_signed_in', 'pageviews_subscribers', 'timespent_all', 'timespent_signed_in', 'timespent_subscribers']);

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
                "articles.pageviews_all",
                "articles.pageviews_signed_in",
                "articles.pageviews_subscribers",
                "articles.timespent_all",
                "articles.timespent_signed_in",
                "articles.timespent_subscribers",
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
                'avg(nullif(pageviews_all, 0)) as pageviews_all_avg',
                'avg(nullif(pageviews_signed_in, 0)) as pageviews_signed_in_avg',
                'avg(nullif(pageviews_subscribers, 0)) as pageviews_subscribers_avg',
                'avg(nullif(timespent_all, 0)) as timespent_all_avg',
                'avg(nullif(timespent_signed_in, 0)) as timespent_signed_in_avg',
                'avg(nullif(timespent_subscribers, 0)) as timespent_subscribers_avg',
                'avg(nullif(timespent_all / pageviews_all, 0)) as average_timespent_all',
                'avg(nullif(timespent_signed_in / pageviews_signed_in, 0)) as average_timespent_signed_in',
                'avg(nullif(timespent_subscribers / pageviews_subscribers, 0)) as average_timespent_subscribers',
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
            ->addColumn('pageviews_all', function (Article $article) use ($averages) {
                $arr = [$article->pageviews_all];
                if ($article->pageviews_all > 0) {
                    $arr[] = $averages->pageviews_all_avg;
                }
                return $arr;
            })
            ->addColumn('pageviews_signed_in', function (Article $article) use ($averages) {
                $arr = [$article->pageviews_signed_in];
                if ($article->pageviews_signed_in > 0) {
                    $arr[] = $averages->pageviews_signed_in_avg;
                }
                return $arr;
            })
            ->addColumn('pageviews_subscribers', function (Article $article) use ($averages) {
                $arr = [$article->pageviews_subscribers];
                if ($article->pageviews_subscribers > 0) {
                    $arr[] = $averages->pageviews_subscribers_avg;
                }
                return $arr;
            })
            ->addColumn('avg_timespent_all', function (Article $article) use ($averages) {
                if (!$article->timespent_all || !$article->pageviews_all) {
                    return [0];
                }
                return [round($article->timespent_all / $article->pageviews_all), $averages->average_timespent_all];
            })
			->addColumn('avg_timespent_signed_in', function (Article $article) use ($averages) {
				if (!$article->timespent_signed_in || !$article->pageviews_signed_in) {
					return [0];
				}
				return [round($article->timespent_signed_in / $article->pageviews_signed_in), $averages->average_timespent_signed_in];
			})
			->addColumn('avg_timespent_subscribers', function (Article $article) use ($averages) {
				if (!$article->timespent_subscribers || !$article->pageviews_subscribers) {
					return [0];
				}
				return [round($article->timespent_subscribers / $article->pageviews_subscribers), $averages->average_timespent_subscribers];
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
            ->orderColumn('avg_sum', 'timespent_sum / pageviews_all $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('timespent_sum', 'timespent_sum $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_sum', 'conversions_sum $1')
            ->orderColumn('conversions_avg', 'conversions_avg $1')
            ->make(true);
    }
}
