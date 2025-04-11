<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Http\Requests\SnippetRequest;
use Remp\CampaignModule\Http\Resources\SnippetResource;
use Remp\CampaignModule\Snippet;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class SnippetController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('campaign::snippets.index'),
            'json' => SnippetResource::collection(Snippet::paginate()),
        ]);
    }

    public function json(DataTables $dataTables)
    {
        $snippets = Snippet::select();

        /** @var QueryDataTable $datatable */
        $datatable = $dataTables->of($snippets);
        return $datatable
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
        return view('campaign::snippets.create', [
            'snippet' => new Snippet(),
        ]);
    }

    public function edit(Snippet $snippet)
    {
        return view('campaign::snippets.edit', [
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
