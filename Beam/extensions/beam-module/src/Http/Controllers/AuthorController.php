<?php

namespace Remp\BeamModule\Http\Controllers;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\ArticleAuthor;
use Remp\BeamModule\Model\ArticlesDataTable;
use Remp\BeamModule\Model\Author;
use Remp\BeamModule\Model\Conversion;
use Remp\BeamModule\Http\Resources\AuthorResource;
use Remp\BeamModule\Model\Rules\ValidCarbonDate;
use Remp\BeamModule\Model\Tag;
use Remp\BeamModule\Model\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Html;
use Yajra\DataTables\QueryDataTable;

class AuthorController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('beam::authors.index', [
                'authors' => Author::query()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
                'contentTypes' => array_merge(
                    ['all'],
                    Article::groupBy('content_type')->pluck('content_type')->toArray()
                ),
                'contentType' => $request->input('content_type', 'all'),
            ]),
            'json' => AuthorResource::collection(Author::paginate($request->get('per_page', 15)))->preserveQuery(),
        ]);
    }

    public function show(Author $author, Request $request)
    {
        return response()->format([
            'html' => view('beam::authors.show', [
                'author' => $author,
                'contentTypes' => Article::groupBy('content_type')->pluck('content_type', 'content_type'),
                'sections' => Section::query()->pluck('name', 'id'),
                'tags' => Tag::query()->pluck('name', 'id'),
                'authors' => Author::query()->pluck('name', 'id'),
                'publishedFrom' => $request->input('published_from', 'today - 30 days'),
                'publishedTo' => $request->input('published_to', 'now'),
                'conversionFrom' => $request->input('conversion_from', 'today - 30 days'),
                'conversionTo' => $request->input('conversion_to', 'now'),
            ]),
            'json' => new AuthorResource($author),
        ]);
    }

    public function dtAuthors(Request $request, Datatables $datatables)
    {
        $request->validate([
            'published_from' => ['sometimes', new ValidCarbonDate],
            'published_to' => ['sometimes', new ValidCarbonDate],
            'conversion_from' => ['sometimes', new ValidCarbonDate],
            'conversion_to' => ['sometimes', new ValidCarbonDate],
        ]);

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

        $conversionsQuery = Conversion::selectRaw(implode(',', [
            'author_id',
            'count(distinct conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
        ]))
            ->leftJoin('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->leftJoin('articles', 'article_author.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy('author_id');

        $pageviewsQuery = Article::selectRaw(implode(',', [
            'author_id',
            'COALESCE(SUM(pageviews_all), 0) AS pageviews_all',
            'COALESCE(SUM(pageviews_signed_in), 0) AS pageviews_signed_in',
            'COALESCE(SUM(pageviews_subscribers), 0) AS pageviews_subscribers',
            'COALESCE(SUM(timespent_all), 0) AS timespent_all',
            'COALESCE(SUM(timespent_signed_in), 0) AS timespent_signed_in',
            'COALESCE(SUM(timespent_subscribers), 0) AS timespent_subscribers',
            'COUNT(*) as articles_count'
        ]))
            ->leftJoin('article_author', 'articles.id', '=', 'article_author.article_id')
            ->ofSelectedProperty()
            ->groupBy('author_id');

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

        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
            $pageviewsQuery->where('content_type', '=', $request->input('content_type'));
        }

        $authors = Author::selectRaw(implode(",", $cols))
            ->leftJoin(DB::raw("({$conversionsQuery->toSql()}) as c"), 'authors.id', '=', 'c.author_id')->addBinding($conversionsQuery->getBindings())
            ->leftJoin(DB::raw("({$pageviewsQuery->toSql()}) as pv"), 'authors.id', '=', 'pv.author_id')->addBinding($pageviewsQuery->getBindings())
            // has to be below manually added bindings
            ->ofSelectedProperty()
            ->groupBy(['authors.name', 'authors.id', 'articles_count', 'conversions_count', 'conversions_amount', 'pageviews_all',
                'pageviews_signed_in', 'pageviews_subscribers', 'timespent_all', 'timespent_signed_in', 'timespent_subscribers']);

        $conversionsQuery = Conversion::selectRaw('count(distinct conversions.id) as count, sum(amount) as sum, currency, article_author.author_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'article_author.article_id', '=', 'articles.id')
            ->ofSelectedProperty()
            ->groupBy(['article_author.author_id', 'conversions.currency']);

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
        if ($request->input('content_type') && $request->input('content_type') !== 'all') {
            $conversionsQuery->where('content_type', '=', $request->input('content_type'));
        }

        $conversionAmounts = [];
        $conversionCounts = [];
        foreach ($conversionsQuery->get() as $record) {
            $conversionAmounts[$record['author_id']][$record->currency] = $record['sum'];
            if (!isset($conversionCounts[$record['author_id']])) {
                $conversionCounts[$record['author_id']] = 0;
            }
            $conversionCounts[$record['author_id']] += $record['count'];
        }

        /** @var QueryDataTable $datatable */
        $datatable = $datatables->of($authors);
        return $datatable
            ->addColumn('id', function (Author $author) {
                return $author->id;
            })
            ->addColumn('name', function (Author $author) {
                return [
                    'url' => route('authors.show', ['author' => $author]),
                    'text' => $author->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) use ($request) {
                if ($request->input('search')['value'] === $value) {
                    $query->where(function (Builder $query) use ($value) {
                        $query->where('authors.name', 'like', '%' . $value . '%');
                    });
                } else {
                    $authorIds = explode(',', $value);
                    $query->where(function (Builder $query) use ($authorIds) {
                        $query->whereIn('authors.id', $authorIds);
                    });
                }
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
                return $amounts ?: [0];
            })
            ->orderColumn('articles_count', 'articles_count $1')
            ->orderColumn('conversions_count', 'conversions_count $1')
            ->orderColumn('conversions_amount', 'conversions_amount $1')
            ->orderColumn('pageviews_all', 'pageviews_all $1')
            ->orderColumn('pageviews_signed_in', 'pageviews_signed_in $1')
            ->orderColumn('pageviews_subscribers', 'pageviews_subscribers $1')
            ->orderColumn('avg_timespent_all', 'avg_timespent_all $1')
            ->orderColumn('avg_timespent_signed_in', 'avg_timespent_signed_in $1')
            ->orderColumn('avg_timespent_subscribers', 'avg_timespent_subscribers $1')
            ->orderColumn('id', 'authors.id $1')
            ->setTotalRecords(PHP_INT_MAX)
            ->setFilteredRecords(PHP_INT_MAX)
            ->make(true);
    }

    public function dtArticles(Author $author, Request $request, DataTables $datatables, ArticlesDataTable $articlesDataTable)
    {
        $articlesDataTable->setAuthor($author);
        return $articlesDataTable->getDataTable($request, $datatables);
    }
}
