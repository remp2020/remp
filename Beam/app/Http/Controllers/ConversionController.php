<?php

namespace App\Http\Controllers;

use App\Conversion;
use App\Http\Request;
use App\Http\Requests\ConversionRequest;
use App\Http\Resources\ConversionResource;
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
            ->with(['article', 'article.authors', 'article.sections'])
            ->get();

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
}
