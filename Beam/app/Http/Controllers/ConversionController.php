<?php

namespace App\Http\Controllers;

use App\Author;
use App\Conversion;
use App\Http\Request;
use App\Http\Requests\ConversionRequest;
use App\Http\Requests\ConversionUpsertRequest;
use App\Http\Resources\ConversionResource;
use App\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Remp\LaravelHelpers\Resources\JsonResource;
use Yajra\Datatables\Datatables;

class ConversionController extends Controller
{
    public function index(Request $request)
    {
        return response()->format([
            'html' => view('conversions.index', [
                'authors' => Author::all()->pluck('name', 'id'),
                'sections' => Section::all()->pluck('name', 'id'),
                'conversionFrom' => $request->get('conversion_from', Carbon::now()->subMonth()),
                'conversionTo' => $request->get('conversion_to', Carbon::now()),
            ]),
            'json' => ConversionResource::collection(Conversion::paginate()),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $conversions = Conversion::select('conversions.*')
            ->with(['article', 'article.authors', 'article.sections'])
            ->join('articles', 'articles.id', '=', 'conversions.article_id')
            ->join('article_author', 'articles.id', '=', 'article_author.article_id')
            ->join('article_section', 'articles.id', '=', 'article_section.article_id');

        if ($request->input('conversion_from')) {
            $conversions->where('paid_at', '>=', $request->input('conversion_from'));
        }
        if ($request->input('conversion_to')) {
            $conversions->where('paid_at', '<=', $request->input('conversion_to'));
        }

        return $datatables->of($conversions)
            ->addColumn('article.title', function (Conversion $conversion) {
                return \HTML::link($conversion->article->url, $conversion->article->title);
            })
            ->filterColumn('article.authors[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_author.author_id', $values);
            })
            ->filterColumn('article.sections[, ].name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('article_section.section_id', $values);
            })
            ->make(true);
    }

    public function store(ConversionRequest $request)
    {
        $conversion = new Conversion();
        $conversion->fill($request->all());
        $conversion->save();

        return response()->format([
            'html' => redirect(route('conversions.index'))->with('success', 'Conversion created'),
            'json' => new ConversionResource($conversion),
        ]);
    }

    public function upsert(ConversionUpsertRequest $request)
    {
        foreach ($request->get('conversions', []) as $c) {
            $conversion = Conversion::firstOrNew([
                'transaction_id' => $c['transaction_id'],
            ]);
            $conversion->fill($c);
            $conversion->save();
        }

        return response()->format([
            'html' => redirect(route('conversions.index'))->with('success', 'Conversions created'),
            'json' => new JsonResource([]),
        ]);
    }
}
