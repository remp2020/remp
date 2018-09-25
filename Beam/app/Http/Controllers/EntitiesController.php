<?php

namespace App\Http\Controllers;

use Html;
use App\Entity;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Http\Requests\EntityRequest;
use App\Http\Resources\EntityResource;

class EntitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('entities.index');
    }

    public function json(Request $request, DataTables $datatables)
    {
        $columns = ['id', 'schema', 'name'];
        $entities = Entity::select($columns)->whereNotNull('parent_id');

        return $datatables->of($entities)
            ->addColumn('name', function (Entity $entity) {
                return Html::linkRoute('entities.edit', $entity->name, $entity);
            })
            ->addColumn('params', function (Entity $entity) {
                $params = [];

                foreach ($entity->schema->getParams() as $param) {
                    $type = __("entities.types." . $param["type"]);
                    $name = $param["name"];

                    $params[] = "<strong>{$type}</strong>&nbsp;{$name}";
                }

                return $params;
            })
            ->addColumn('actions', function (Entity $entity) {
                return [
                    'edit' => route('entities.edit', $entity)
                ];
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $entity = new Entity();

        return response()->format([
            'html' => view('entities.create', [
                'entity' => $entity,
                'rootEntities' => Entity::where('parent_id', null)->get()
            ]),
            'json' => new EntityResource($entity)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  EntityRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EntityRequest $request)
    {
        $entity = new Entity($request->all());
        $entity->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'entities.index',
                    self::FORM_ACTION_SAVE => 'entities.edit',
                ],
                $entity
            )->with('success', sprintf('Entity [%s] was created', $entity->name)),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Entity  $entity
     * @return \Illuminate\Http\Response
     */
    public function edit(Entity $entity)
    {
        return view('entities.edit', [
            'entity' => $entity,
            'rootEntities' => Entity::where('parent_id', null)->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Entity $entity
     * @param  EntityRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Entity $entity, EntityRequest $request)
    {
        $entity->fill($request->all());
        $entity->save();

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'entities.index',
                    self::FORM_ACTION_SAVE => 'entities.edit',
                ],
                $entity
            )->with('success', sprintf('Entity [%s] was updated', $entity->name)),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Entity  $entity
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response()->format([
            'html' => redirect(route('entities.index'))->with('success', 'Entity removed'),
            'json' => new EntityResource([]),
        ]);
    }

    /**
     * Ajax validate form method.
     *
     * @param EntityRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function validateForm(EntityRequest $request, Entity $entity = null)
    {
        return response()->json(false);
    }
}
