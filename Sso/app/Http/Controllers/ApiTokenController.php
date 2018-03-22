<?php

namespace App\Http\Controllers;

use App\ApiToken;
use App\Http\Requests\ApiTokenRequest;
use App\Http\Resources\ApiTokenResource;
use Yajra\DataTables\DataTables;

class ApiTokenController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('api_tokens.index'),
            'json' => ApiTokenResource::collection(ApiToken::paginate()),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $apiTokens = ApiToken::select()->get();

        return $dataTables->of($apiTokens)
            ->addColumn('actions', function (ApiToken $apiToken) {
                return [
                    'edit' => route('api-tokens.edit', $apiToken),
                    'destroy' => route('api-tokens.destroy', $apiToken),
                ];
            })
            ->addColumn('action_methods', [
                'destroy' => 'DELETE',
            ])
            ->rawColumns(['actions', 'active'])
            ->setRowId('id')
            ->make(true);
    }

    public function create()
    {
        $apiToken = new ApiToken();
        $apiToken->fill(old());

        return view('api_tokens.create', [
            'apiToken' => $apiToken,
        ]);
    }

    public function store(ApiTokenRequest $request)
    {
        $apiToken = new ApiToken();
        $apiToken->fill($request->all());
        $apiToken->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'api-tokens.index',
                    self::FORM_ACTION_SAVE => 'api-tokens.edit',
                ],
                $apiToken
            )->with('success', sprintf('API token [%s] was created', $apiToken->token)),
            'json' => new ApiTokenResource($apiToken),
        ]);
    }

    public function show(ApiToken $apiToken)
    {
        return response()->format([
            'html' => view('api-tokens.show', [
                'apiToken' => $apiToken,
            ]),
            'json' => new ApiTokenResource($apiToken),
        ]);
    }

    public function edit(ApiToken $apiToken)
    {
        $apiToken->fill(old());

        return view('api_tokens.edit', [
            'apiToken' => $apiToken,
        ]);
    }

    public function update(ApiTokenRequest $request, ApiToken $apiToken)
    {
        $apiToken->fill($request->all());
        $apiToken->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'api-tokens.index',
                    self::FORM_ACTION_SAVE => 'api-tokens.edit',
                ],
                $apiToken
            )->with('success', sprintf('API token [%s] was updated', $apiToken->token)),
            'json' => new ApiTokenResource($apiToken),
        ]);
    }

    public function destroy(ApiToken $apiToken)
    {
        $apiToken->delete();
        return response()->format([
            'html' => redirect(route('api-tokens.index'))->with('success', sprintf('API token [%s] was removed', $apiToken->token)),
            'json' => new ApiTokenResource([]),
        ]);
    }
}
