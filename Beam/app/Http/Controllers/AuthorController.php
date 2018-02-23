<?php

namespace App\Http\Controllers;

use App\Author;
use App\Conversion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\DataTables;

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
//            'json' => AuthorResource::collection(Author::paginate()),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $cols = [
            'authors.id',
            'authors.name',
            'count(articles.id) as articles_count',
            'count(conversions.id) as conversions_count',
            'sum(conversions.amount) as conversions_amount',
            'coalesce(sum(article_pageviews.sum), 0) as pageviews_count',
            'coalesce(sum(article_timespents.sum), 0) as pageviews_timespent',
            'coalesce(sum(article_timespents.sum) / sum(article_pageviews.sum), 0) as avg_timespent',
        ];
        $authors = Author::selectRaw(implode(",", $cols))
            ->leftJoin('article_author', 'authors.id', '=', 'article_author.author_id')
            ->leftJoin('articles', 'articles.id', '=', 'article_author.article_id')
            ->leftJoin('conversions', 'conversions.article_id', '=', 'article_author.article_id')
            ->leftJoin('article_pageviews', 'articles.id', '=', 'article_pageviews.article_id')
            ->leftJoin('article_timespents', 'articles.id', '=', 'article_timespents.article_id')
            ->groupBy(['authors.name', 'authors.id']);

        $conversionsQuery = Conversion::selectRaw('sum(amount) as sum, currency, article_author.author_id')
            ->join('article_author', 'conversions.article_id', '=', 'article_author.article_id')
            ->join('articles', 'article_author.article_id', '=', 'articles.id')
            ->groupBy(['article_author.author_id', 'conversions.currency']);

        if ($request->input('published_from')) {
            $authors->where('published_at', '>=', $request->input('published_from'));
            $conversionsQuery->where('published_at', '>=', $request->input('published_from'));
        }
        if ($request->input('published_to')) {
            $authors->where('published_at', '<=', $request->input('published_to'));
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
                $amount = null;
                foreach ($conversions[$author->id] as $currency => $c) {
                    $c = round($c, 2);
                    $amount .= "{$c} {$currency}";
                }
                return $amount ?? 0;
            })
            ->make(true);
    }
}
