<?php

namespace App\Http\Controllers;

use App\Conversion;
use App\Http\Request;
use App\Http\Requests\ConversionRequest;
use App\Http\Requests\ConversionUpsertRequest;
use App\Http\Resources\ConversionResource;
use Remp\LaravelHelpers\Resources\JsonResource;
use Yajra\Datatables\Datatables;

class ConversionController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('conversions.index'),
            'json' => ConversionResource::collection(Conversion::paginate()),
        ]);
    }

    public function json(Request $request, Datatables $datatables)
    {
        $conversions = Conversion::select()
            ->with(['article', 'article.authors', 'article.sections']);

        return $datatables->of($conversions)
            ->addColumn('article.title', function (Conversion $conversion) {
                return \HTML::link($conversion->article->url, $conversion->article->title);
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
