<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Remp\BeamModule\Http\Requests\ArticleRequest;
use Remp\BeamModule\Http\Requests\ArticlesListRequest;
use Remp\BeamModule\Http\Resources\ArticleResource;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Config\ConversionRateConfig;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Remp\BeamModule\Model\Section;
use Remp\BeamModule\Model\Tag;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\QueryDataTable;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ArticlesListRequest $request)
    {
        $articles = null;
        $articlesBuilder = Article::with(['authors:id,external_id', 'sections:id,external_id', 'tags:id,external_id']);

        $externalIds = $request->input('external_ids', []);
        if (count($externalIds)) {
            $articles = $articlesBuilder->whereIn('external_id', array_map('strval', $externalIds))->get();
        }

        if (!$articles) {
            $ids = $request->input('ids', []);
            if (count($request->ids)) {
                $articles = $articlesBuilder->whereIntegerInRaw('id', $ids)->get();
            }
        }

        $publishedFrom = $request->input('published_from');
        $publishedTo = $request->input('published_to');

        if (!$articles && ($publishedFrom || $publishedTo)) {
            if ($publishedFrom) {
                $articlesBuilder = $articlesBuilder->whereDate('published_at', '>=', $publishedFrom);
            }
            if ($publishedTo) {
                $articlesBuilder = $articlesBuilder->whereDate('published_at', '<', $publishedTo);
            }
            $articles = $articlesBuilder->get();
        }

        $contentType = $request->input('content_type');
        if ($contentType) {
            $articles = $articlesBuilder->where('content_type', '=', $contentType)->get();
        }

        if (!$articles) {
            $articles = $articlesBuilder->paginate($request->get('per_page', 15));
        }

        return response()->format([
            'html' => redirect()->route('articles.pageviews'),
            'json' => ArticleResource::collection($articles)->preserveQuery(),
        ]);
    }

    public function conversions(Request $request)
    {
        return response()->format([
            'html' => view('beam::articles.conversions', [
                'authors' => Author::all(['name', 'id'])->pluck('name', 'id'),
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::all(['name', 'id'])->pluck('name', 'id'),
                'tags' => Tag::all(['name', 'id'])->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtConversions(Request $request, Datatables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
        ]);

        $articlesQuery = Article::query()->selectRaw(implode(',', [
                "articles.id",
                "articles.external_id",
                "articles.title",
                "articles.content_type",
                "articles.url",
                "articles.published_at",
                "articles.pageviews_all",
                "count(conversions.id) as conversions_count",
                "coalesce(sum(conversions.amount), 0) as conversions_sum",
                "avg(conversions.amount) as conversions_avg",
                "coalesce(cast(count(conversions.id) AS DECIMAL(15, 10)) / articles.pageviews_all, 0) as conversions_rate",
            ]))
            ->with(['authors', 'sections', 'tags'])
            ->leftJoin('conversions', 'articles.id', '=', 'conversions.article_id')
            ->groupBy(['articles.id', 'articles.title', 'articles.url', 'articles.published_at'])
            ->ofSelectedProperty();

        $conversionsQuery = \DB::table('conversions')
            ->select([
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as sum'),
                DB::raw('avg(amount) as avg'),
                'currency',
                'article_id',
                'articles.external_id',
            ])
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->groupBy(['conversions.article_id', 'articles.external_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $publishedFrom = Carbon::parse($request->input('published_from'), $request->input('tz'));
            $articlesQuery->where('published_at', '>=', $publishedFrom);
        }
        if ($request->input('published_to')) {
            $publishedTo = Carbon::parse($request->input('published_to'), $request->input('tz'));
            $articlesQuery->where('published_at', '<=', $publishedTo);
        }
        if ($request->input('conversion_from')) {
            $conversionFrom = Carbon::parse($request->input('conversion_from'), $request->input('tz'));
            $articlesQuery->where('paid_at', '>=', $conversionFrom);
            $conversionsQuery->where('paid_at', '>=', $conversionFrom);
        }
        if ($request->input('conversion_to')) {
            $conversionTo = Carbon::parse($request->input('conversion_to'), $request->input('tz'));
            $articlesQuery->where('paid_at', '<=', $conversionTo);
            $conversionsQuery->where('paid_at', '<=', $conversionTo);
        }

        $columns = $request->input('columns');
        $authorsIndex = array_search('authors', array_column($columns, 'name'), true);
        if (isset($columns[$authorsIndex]['search']['value'])) {
            $values = explode(',', $columns[$authorsIndex]['search']['value']);
            $articlesQuery->join('article_author', 'articles.id', '=', 'article_author.article_id')
                ->whereIn('article_author.author_id', $values);
        }
        $sectionsIndex = array_search('sections[, ].name', array_column($columns, 'name'), true);
        if (isset($columns[$sectionsIndex]['search']['value'])) {
            $values = explode(',', $columns[$sectionsIndex]['search']['value']);
            $articlesQuery->join('article_section', 'articles.id', '=', 'article_section.article_id')
                ->whereIn('article_section.section_id', $values);
        }
        $tagsIndex = array_search('tags[, ].name', array_column($columns, 'name'), true);
        if (isset($columns[$tagsIndex]['search']['value'])) {
            $values = explode(',', $columns[$tagsIndex]['search']['value']);
            $articlesQuery->join('article_tag', 'articles.id', '=', 'article_tag.article_id')
                ->whereIn('article_tag.tag_id', $values);
        }

        $conversionsQuery->whereIntegerInRaw('article_id', $articlesQuery->pluck('id'));

        $conversionSums = [];
        $conversionAverages = [];

        foreach ($conversionsQuery->get() as $record) {
            $conversionSums[$record->article_id][$record->currency] = $record->sum;
            $conversionAverages[$record->article_id][$record->currency] = $record->avg;
        }

        $conversionRateConfig = ConversionRateConfig::build();

        /** @var EloquentDataTable $dt */
        $dt =  $datatables->of($articlesQuery);

        return $dt
            ->addColumn('id', function (Article $article) {
                return $article->id;
            })
            ->addColumn('title', function (Article $article) {
                return [
                    'url' => route('articles.show', ['article' => $article->id]),
                    'text' => $article->title,
                ];
            })
            ->addColumn('conversions_rate', function (Article $article) use ($conversionRateConfig) {
                return Article::computeConversionRate($article->conversions_count, $article->pageviews_all, $conversionRateConfig);
            })
            ->addColumn('amount', function (Article $article) use ($conversionSums) {
                if (!isset($conversionSums[$article->id])) {
                    return [0];
                }
                $amounts = [];
                foreach ($conversionSums[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amounts[] = "{$c} {$currency}";
                }
                return $amounts ?: [0];
            })
            ->addColumn('average', function (Article $article) use ($conversionAverages) {
                if (!isset($conversionAverages[$article->id])) {
                    return [0];
                }
                $average = [];
                foreach ($conversionAverages[$article->id] as $currency => $c) {
                    $c = round($c, 2);
                    $average[] = "{$c} {$currency}";
                }
                return $average ?: [0];
            })
            ->addColumn('authors', function (Article $article) {
                $authors = $article->authors->map(function (Author $author) {
                    return ['link' => html()->a(route('authors.show', $author), $author->name)];
                });
                return $authors->implode('link', '<br/>');
            })
            ->orderColumn('amount', 'conversions_sum $1')
            ->orderColumn('average', 'conversions_avg $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_rate', 'conversions_rate $1')
            ->orderColumn('id', 'articles.id $1')
            ->filterColumn('title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('content_type', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('articles.content_type', $values);
            })
            ->filterColumn('authors', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.section_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('tags[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_tag', 'articles.id', '=', 'article_tag.article_id', 'left')
                    ->whereIn('article_tag.tag_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->rawColumns(['authors'])
            ->make();
    }

    public function pageviews(Request $request)
    {
        return response()->format([
            'html' => view('beam::articles.pageviews', [
                'authors' => Author::query()->pluck('name', 'id'),
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::query()->pluck('name', 'id'),
                'tags' => Tag::query()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
            ]),
            'json' => ArticleResource::collection(Article::paginate()),
        ]);
    }

    public function dtPageviews(Request $request, Datatables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
        ]);

        $articles = Article::selectRaw('articles.*,' .
            'CASE pageviews_all WHEN 0 THEN 0 ELSE (pageviews_subscribers/pageviews_all)*100 END AS pageviews_subscribers_ratio')
            ->with(['authors', 'sections', 'tags'])
            ->ofSelectedProperty();

        if ($request->input('published_from')) {
            $articles->where('published_at', '>=', Carbon::parse($request->input('published_from'), $request->input('tz')));
        }
        if ($request->input('published_to')) {
            $articles->where('published_at', '<=', Carbon::parse($request->input('published_to'), $request->input('tz')));
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
            ->addColumn('avg_sum_all', function (Article $article) {
                if (!$article->timespent_all || !$article->pageviews_all) {
                    return 0;
                }
                return round($article->timespent_all / $article->pageviews_all);
            })
            ->addColumn('avg_sum_signed_in', function (Article $article) {
                if (!$article->timespent_signed_in || !$article->pageviews_signed_in) {
                    return 0;
                }
                return round($article->timespent_signed_in / $article->pageviews_signed_in);
            })
            ->addColumn('avg_sum_subscribers', function (Article $article) {
                if (!$article->timespent_subscribers || !$article->pageviews_subscribers) {
                    return 0;
                }
                return round($article->timespent_subscribers / $article->pageviews_subscribers);
            })
            ->addColumn('authors', function (Article $article) {
                $authors = $article->authors->map(function (Author $author) {
                    return ['link' => html()->a(route('authors.show', $author), $author->name)];
                });
                return $authors->implode('link', '<br/>');
            })
            ->filterColumn('title', function (Builder $query, $value) {
                $query->where('articles.title', 'like', '%' . $value . '%');
            })
            ->filterColumn('content_type', function (Builder $query, $value) {
                $values = explode(',', $value);
                $query->whereIn('articles.content_type', $values);
            })
            ->filterColumn('authors', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_author', 'articles.id', '=', 'article_author.article_id', 'left')
                    ->whereIn('article_author.author_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('sections[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_section', 'articles.id', '=', 'article_section.article_id', 'left')
                    ->whereIn('article_section.section_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->filterColumn('tags[, ].name', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('articles')
                    ->select(['articles.id'])
                    ->join('article_tag', 'articles.id', '=', 'article_tag.article_id', 'left')
                    ->whereIn('article_tag.tag_id', $values);
                $query->whereIn('articles.id', $filterQuery);
            })
            ->orderColumn('avg_sum_all', 'timespent_all / pageviews_all $1')
            ->orderColumn('avg_sum_signed_in', 'timespent_signed_in / pageviews_signed_in $1')
            ->orderColumn('avg_sum_subscribers', 'timespent_subscribers / pageviews_subscribers $1')
            ->orderColumn('pageviews_subscribers_ratio', 'pageviews_subscribers_ratio $1')
            ->orderColumn('id', 'articles.id $1')
            ->rawColumns(['authors'])
            ->make(true);
    }

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

        $article->tags()->detach();
        foreach ($request->get('tags', []) as $tagName) {
            $tag = Tag::firstOrCreate([
                'name' => $tagName,
            ]);
            $article->tags()->attach($tag);
        }

        $article->authors()->detach();
        foreach ($request->get('authors', []) as $authorName) {
            $section = Author::firstOrCreate([
                'name' => $authorName,
            ]);
            $article->authors()->attach($section);
        }

        $article->load(['authors', 'sections', 'tags']);

        return response()->format([
            'html' => redirect(route('articles.pageviews'))->with('success', 'Article created'),
            'json' => new ArticleResource($article),
        ]);
    }
}
