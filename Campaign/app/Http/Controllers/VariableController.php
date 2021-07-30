<?php

namespace App\Http\Controllers;

use App\Http\Requests\VariableRequest;
use App\Http\Resources\VariableResource;
use App\Variable;
use Yajra\DataTables\DataTables;

class VariableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('variables.index'),
            'json' => VariableResource::collection(Variable::paginate()),
        ]);
    }

    public function json(DataTables $dataTables)
    {
        $variables = Variable::select()->get();

        return $dataTables->of($variables)
            ->addColumn('actions', function (Variable $variable) {
                return [
                    'edit' => route('variables.edit', $variable),
                ];
            })
            ->addColumn('name', function (Variable $banner) {
                return \Html::linkRoute('variables.edit', $banner->name, $banner);
            })
            ->setRowId('id')
            ->make(true);
    }

    public function create()
    {
        return view('variables.create', [
            'variable' => new Variable(),
        ]);
    }

    public function edit(Variable $variable)
    {
        return view('variables.edit', [
            'variable' => $variable,
        ]);
    }

    public function store(VariableRequest $request)
    {
        $variable = new Variable();
        $variable->fill($request->all());
        $variable->save();

        $message = ['success' => sprintf('Variable [%s] was created.', $variable->name)];

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'variables.index',
                    self::FORM_ACTION_SAVE => 'variables.edit',
                ],
                $variable
            )->with($message),
            'json' => new VariableResource($variable),
        ]);
    }

    public function update(VariableRequest $request, Variable $variable)
    {
        $variable->fill($request->all());
        $variable->save();

        $message = ['success' => sprintf('Variable [%s] was updated.', $variable->name)];

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'variables.index',
                    self::FORM_ACTION_SAVE => 'variables.edit',
                ],
                $variable
            )->with($message),
            'json' => new VariableResource($variable),
        ]);
    }
}
