<?php

namespace Remp\BeamModule\Http\Controllers;

use Illuminate\Http\Request;
use Remp\BeamModule\Http\Requests\EntityRequest;
use Remp\BeamModule\Http\Resources\EntityResource;
use Remp\BeamModule\Model\Entity;
use Remp\BeamModule\Model\EntityParam;
use Yajra\DataTables\DataTables;

class EntitiesController extends Controller
{
    public function index()
    {
        return view('beam::entities.index');
    }

    public function json(Request $request, DataTables $datatables)
    {
        $columns = ['id', 'name'];
        $entities = Entity::select($columns)->with("params")->whereNotNull('parent_id');

        return $datatables->of($entities)
            ->addColumn('name', function (Entity $entity) {
                return [
                    'url' => route('entities.edit', ['entity' => $entity]),
                    'text' => $entity->name,
                ];
            })
            ->addColumn('params', function (Entity $entity) {
                $params = [];

                foreach ($entity->params as $param) {
                    $type = __("entities.types." . $param->type);
                    $params[] = "<strong>{$type}</strong>&nbsp;{$param->name}";
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

    public function create()
    {
        $entity = new Entity();

        return response()->format([
            'html' => view('beam::entities.create', [
                'entity' => $entity,
                'rootEntities' => Entity::where('parent_id', null)->get()
            ]),
            'json' => new EntityResource($entity)
        ]);
    }

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

    public function edit(Entity $entity)
    {
        if ($entity->isRootEntity()) {
            return response('Forbidden', 403);
        }

        return view('beam::entities.edit', [
            'entity' => $entity,
            'rootEntities' => Entity::where('parent_id', null)->get()
        ]);
    }

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

    public function destroy(Entity $entity)
    {
        $entity->delete();

        return response()->format([
            'html' => redirect(route('entities.index'))->with('success', 'Entity removed'),
            'json' => new EntityResource([]),
        ]);
    }

    public function validateForm(EntityRequest $request, Entity $entity = null)
    {
        return response()->json(false);
    }

    public function saveEntity(Entity $entity, EntityRequest $request)
    {
        $entity->fill($request->all());
        $entity->save();

        $paramsToDelete = $request->get("params_to_delete") ?? [];
        EntityParam::whereIn("id", $paramsToDelete)->delete();

        $paramsData = $request->get("params") ?? [];
        foreach ($paramsData as $paramData) {
            $param = EntityParam::findOrNew($paramData["id"]);
            $param->fill($paramData);
            $param->entity()->associate($entity);
            $param->save();
        }

        return $entity;
    }
}
