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
        $conversionsAmountSubquery = <<<SQL
(select sum(amount) from `conversions`
inner join article_author on conversions.article_id = article_author.article_id
where `article_author`.`author_id` = `authors`.`id`) as `conversions_amount`
SQL;
        $conversionCount = Conversion::selectRaw('COUNT(*) as count, author_id')
            ->join('article_author', 'article_author.article_id', '=', 'conversions.article_id')
            ->join('articles', 'articles.id', '=', 'article_author.author_id')
            ->groupBy(['article_id'])->toSql();

        // todo zistit pocet a sumu konverzii pre autora pre clanky v dodanom casovom rozpati

        $authors = Author::selectRaw("authors.*, {$conversionsAmountSubquery}")
            ->join('article_author', 'authors.id', '=', 'article_author.author_id')
            ->join('articles', 'articles.id', '=', 'article_author.article_id')
            ->leftJoin(\DB::raw($conversionCount), '1', '=', '2');


//            ->withCount(['articles', 'conversions' => function ($query) use ($request) {
//                if ($request->input('published_from')) {
//                    $query->where('published_at', '>=', $request->input('published_from'));
//                }
//                if ($request->input('published_to')) {
//                    $query->where('published_at', '<=', $request->input('published_to'));
//                }
//            }]);

//        if ($request->input('published_from')) {
//            $authors->where('published_at', '>=', $request->input('published_from'));
//        }
//        if ($request->input('published_to')) {
//            $authors->where('published_at', '<=', $request->input('published_to'));
//        }

        return $datatables->of($authors)
            ->filterColumn('name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('authors.id', $values);
            })
            ->make(true);

//        ->join('articles', function (JoinClause $join) use ($request) {
//        $join->on('articles.id', '=', 'article_author.article_id');
//        if ($request->input('published_from')) {
//            $join->on('published_at', '>=', \DB::raw("'{$request->input('published_from')}'"));
//        }
//        if ($request->input('published_to')) {
//            $join->on('published_at', '<=', \DB::raw("'{$request->input('published_to')}'"));
//        }
//    })
    }
}
