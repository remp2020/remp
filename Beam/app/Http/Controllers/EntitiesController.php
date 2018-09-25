<?php

namespace App\Http\Controllers;

use App\EntitySchema;
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
        $columns = ['id', 'name'];
        $entities = Entity::select($columns)->whereNotNull('parent_id');

        return $datatables->of($entities)
            ->addColumn('name', function (Entity $entity) {
                return Html::linkRoute('entities.edit', $entity->name, $entity);
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
        $entity = new Entity();

        $entity = $this->saveEntity($entity, $request);

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
        $entity = $this->saveEntity($entity, $request);

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

    /**
     * Build JSON Schema from request and store entity.
     *
     * @param Entity $entity
     * @param EntityRequest $request
     * @return Entity
     */
    public function saveEntity(Entity $entity, EntityRequest $request)
    {
        $entity->fill($request->only(['name', 'parent_id']));

        $params = $request->get("params");
        $requiredParams = $request->get("required_params") ?? [];
        $schema = [
            'type' => 'object',
            'title' => $request->get('name'),
            'properties' => [],
            'required' => []
        ];

        foreach ($params as $param) {
            $param = array_filter($param);
            $name = $param['name'];

            unset($param['name']);

            $schema['properties'][$name] = $param;

            if (in_array($name, $requiredParams)) {
                $schema['required'][] = $name;
            }
        }

        $entity->schema = json_encode($schema);

        $entity->save();

        return $entity;
    }
}
