<?php

namespace App\Http\Controllers;

use App\Http\Requests\SnippetRequest;
use App\Http\Resources\SnippetResource;
use App\Snippet;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\DataTables;

class SnippetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('snippets.index'),
            'json' => SnippetResource::collection(Snippet::paginate()),
        ]);
    }

    public function json(DataTables $dataTables)
    {
        $snippets = Snippet::select();

        return $dataTables->of($snippets)
            ->addColumn('actions', function (Snippet $snippet) {
                return [
                    'edit' => route('snippets.edit', $snippet),
                ];
            })
            ->addColumn('name', function (Snippet $snippet) {
                return [
                    'url' => route('snippets.edit', ['snippet' => $snippet]),
                    'text' => $snippet->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $query->where('snippets.name', 'like', "%{$value}%");
            })
            ->setRowId('id')
            ->make(true);
    }

    public function create()
    {
        return view('snippets.create', [
            'snippet' => new Snippet(),
        ]);
    }

    public function edit(Snippet $snippet)
    {
        return view('snippets.edit', [
            'snippet' => $snippet,
        ]);
    }

    public function validateForm(SnippetRequest $request, Snippet $snippet = null)
    {
        return response()->json(false);
    }

    public function store(SnippetRequest $request)
    {
        $snippet = new Snippet();
        $snippet->fill($request->all());
        $snippet->save();

        $message = ['success' => sprintf('Snippet [%s] was created.', $snippet->name)];

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'snippets.index',
                    self::FORM_ACTION_SAVE => 'snippets.edit',
                ],
                $snippet
            )->with($message),
            'json' => new SnippetResource($snippet),
        ]);
    }

    public function update(SnippetRequest $request, Snippet $snippet)
    {
        $snippet->fill($request->all());
        $snippet->save();

        $message = ['success' => sprintf('Snippet [%s] was updated.', $snippet->name)];

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'snippets.index',
                    self::FORM_ACTION_SAVE => 'snippets.edit',
                ],
                $snippet
            )->with($message),
            'json' => new SnippetResource($snippet),
        ]);
    }
}
