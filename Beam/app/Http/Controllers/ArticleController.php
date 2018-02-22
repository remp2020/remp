<?php

namespace App\Http\Controllers;

use App\Article;
use App\Author;
use App\Http\Requests\ArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Section;
use Carbon\Carbon;
use HTML;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('articles.index', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function conversions(Request $request)
    {
        return response()->format([
            'html' => view('articles.conversions', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', Carbon::now()->subMonth()),
                'publishedTo' => $request->input('published_to', Carbon::now()),
                'conversionFrom' => $request->input('conversion_from', Carbon::now()->subMonth()),
                'conversionTo' => $request->input('conversion_to', Carbon::now()),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtConversions(Request $request, Datatables $datatables)
    {
        $conversionsSumSubquery = '(select sum(amount) from `conversions` where `articles`.`id` = `conversions`.`article_id`) as `conversions_sum`';
        $articles = Article::selectRaw("articles.*, {$conversionsSumSubquery}")
            ->with(['authors', 'sections'])
            ->withCount('conversions')
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id')
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id');

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', $request->input('published_to'));
        }
        if ($request->input('conversion_from')) {
            $articles->where('paid_at', '>=', $request->input('conversion_from'));
        }
        if ($request->input('conversion_to')) {
            $articles->where('paid_at', '<=', $request->input('conversion_to'));
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::link($article->url, $article->title);
            })
            ->orderColumn('conversions', 'conversions_count $1')
            ->addColumn('amount', function (Article $article) {
                $sum = $article->conversions()->selectRaw('sum(amount) as sum, currency')->groupBy('currency')->pluck('sum', 'currency');
                $amount = null;
                foreach ($sum as $currency => $c) {
                    $amount .= "{$sum[$currency]} $currency";
                }
                return $amount ?? 0;
            })
            ->orderColumn('amount', 'conversions_sum $1')
            ->filterColumn('authors[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_author.author_id', $values);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->make(true);
    }

    public function pageviews(Request $request)
    {
        return response()->format([
            'html' => view('articles.pageviews', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', Carbon::now()->subMonth()),
                'publishedTo' => $request->input('published_to', Carbon::now()),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtPageviews(Request $request, Datatables $datatables)
    {
        $articles = Article::selectRaw("articles.*")
            ->with(['authors', 'sections'])
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id');

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', $request->input('published_to'));
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::link($article->url, $article->title);
            })
            ->addColumn('avg_sum', function (Article $article) {
                if (!$article->timespent_sum || !$article->pageview_sum) {
                    return 0;
                }
                return round($article->timespent_sum / $article->pageview_sum);
            })
            ->orderColumn('avg_sum', 'timespent_sum / pageview_sum $1')
            ->filterColumn('authors[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_author.author_id', $values);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ArticleRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ArticleRequest $request)
    {
        $article = Article::firstOrNew([
            'external_id' => $request->get('external_id'),
        ]);
        $article->fill($request->all());
        $article->save();

        $article->sections()->detach();
        foreach ($request->get('sections', []) as $sectionName) {
            $section = Section::firstOrCreate([
                'name' => $sectionName,
            ]);
            $article->sections()->attach($section);
        }

        $article->authors()->detach();
        foreach ($request->get('authors', []) as $authorName) {
            $section = Author::firstOrCreate([
                'name' => $authorName,
            ]);
            $article->authors()->attach($section);
        }

        $article->load(['authors', 'sections']);

        return response()->format([
            'html' => redirect(route('articles.index'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }
}
