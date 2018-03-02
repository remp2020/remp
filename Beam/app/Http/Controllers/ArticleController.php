<?php

namespace App\Http\Controllers;

use App\Article;
use App\Author;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\ArticleUpsertRequest;
use App\Http\Resources\ArticleResource;
use App\Section;
use Carbon\Carbon;
use HTML;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Remp\LaravelHelpers\Resources\JsonResource;
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
            'html' => view('articles.pageviews', [
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
        $articles = Article::selectRaw(implode(',', [
                "articles.id",
                "articles.title",
                "articles.url",
                "articles.published_at",
                "count(conversions.id) as conversions_count",
                "coalesce(sum(conversions.amount), 0) as conversions_sum",
                "avg(conversions.amount) as conversions_avg"
            ]))
            ->with(['authors', 'sections'])
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id')
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id')
            ->groupBy(['articles.id', 'articles.title', 'articles.url', 'articles.published_at']);

        $conversionsQuery = \DB::table('conversions')
            ->selectRaw('sum(amount) as sum, avg(amount) as avg, currency, article_author.article_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'articles.id', '=', 'article_author.article_id')
            ->groupBy(['article_author.article_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', $request->input('published_from'));
            $conversionsQuery->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', $request->input('published_to'));
            $conversionsQuery->where('published_at', '<=', $request->input('published_to'));
        }
        if ($request->input('conversion_from')) {
            $articles->where('paid_at', '>=', $request->input('conversion_from'));
            $conversionsQuery->where('paid_at', '>=', $request->input('conversion_from'));
        }
        if ($request->input('conversion_to')) {
            $articles->where('paid_at', '<=', $request->input('conversion_to'));
            $conversionsQuery->where('paid_at', '<=', $request->input('conversion_to'));
        }

        $conversionSums = [];
        $conversionAverages = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversionSums[$record->article_id][$record->currency] = $record->sum;
            $conversionAverages[$record->article_id][$record->currency] = $record->avg;
        }

        return $datatables->of($articles)
            ->addColumn('title', function (Article $article) {
                return HTML::link($article->url, $article->title);
            })
            ->orderColumn('conversions', 'conversions_count $1')
            ->addColumn('amount', function (Article $article) use ($conversionSums) {
                if (!isset($conversionSums[$article->id])) {
                    return 0;
                }
                $amount = null;
                foreach ($conversionSums[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amount .= "{$c} {$currency}";
                }
                return $amount ?? 0;
            })
            ->addColumn('average', function (Article $article) use ($conversionAverages) {
                if (!isset($conversionAverages[$article->id])) {
                    return 0;
                }
                $amount = null;
                foreach ($conversionAverages[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amount .= "{$c} {$currency}";
                }
                return $amount ?? 0;
            })
            ->addColumn('authors', function (Article $article) {
                $authors = $article->authors->map(function (Author $author) {
                    return ['link' => HTML::linkRoute('authors.show', $author->name, [$author])];
                });
                return $authors->implode('link', '<br/>');
            })
            ->orderColumn('amount', 'conversions_sum $1')
            ->orderColumn('average', 'conversions_avg $1')
            ->filterColumn('authors[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_author.author_id', $values);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->rawColumns(['authors'])
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
        /** @var Article $article */
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
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }

    public function upsert(ArticleUpsertRequest $request)
    {
        foreach ($request->get('articles', []) as $a) {
            $article = Article::firstOrNew([
                'external_id' => $a['external_id'],
            ]);
            $article->fill($a);
            $article->save();

            $article->sections()->detach();
            foreach ($a['sections'] as $sectionName) {
                $section = Section::firstOrCreate([
                    'name' => $sectionName,
                ]);
                $article->sections()->attach($section);
            }

            $article->authors()->detach();
            foreach ($a['authors'] as $authorName) {
                $section = Author::firstOrCreate([
                    'name' => $authorName,
                ]);
                $article->authors()->attach($section);
            }

            $article->load(['authors', 'sections']);
        }

        return response()->format([
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => new JsonResource([]),
        ]);
    }
}
