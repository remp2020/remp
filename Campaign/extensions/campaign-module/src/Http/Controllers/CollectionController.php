<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignCollection;
use Remp\CampaignModule\Http\Requests\CollectionRequest;
use Remp\CampaignModule\Http\Resources\CollectionResource;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class CollectionController extends Controller
{
    public function index()
    {
        return response()->format([
            'html' => view('campaign::collections.index'),
            'json' => CollectionResource::collection(CampaignCollection::paginate()),
        ]);
    }

    public function create()
    {
        $collection = new CampaignCollection();

        return view('campaign::collections.create', [
            'collection' => $collection,
            'selectedCampaigns' => [],
            'campaigns' => Campaign::all(),
        ]);
    }

    public function edit(CampaignCollection $collection)
    {
        $collection->fill(old());

        return view('campaign::collections.edit', [
            'collection' => $collection->load('campaigns'),
            'selectedCampaigns' => $collection->campaigns()->pluck('campaigns.id'),
            'campaigns' => Campaign::orderBy('created_at', 'DESC')->get(),
        ]);
    }

    public function store(CollectionRequest $request)
    {
        $collection = new CampaignCollection();

        $collection->fill($request->all());
        $collection->save();

        $collection->campaigns()->attach($request->get('campaigns', []));

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'collections.index',
                    self::FORM_ACTION_SAVE => 'collections.edit',
                ],
                $collection
            )->with('success', sprintf(
                "Collection [%s] was created",
                $collection->name,
            ))
        ]);
    }

    public function update(CollectionRequest $request, CampaignCollection $collection)
    {
        $collection->fill($request->all());
        $collection->save();

        $collection->campaigns()->detach();
        $collection->campaigns()->attach($request->get('campaigns', []));

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'collections.index',
                    self::FORM_ACTION_SAVE => 'collections.edit',
                ],
                $collection
            )->with('success', sprintf(
                "Collection [%s] was updated",
                $collection->name,
            ))
        ]);
    }

    public function destroy(CampaignCollection $collection)
    {
        $collection->campaigns()->detach();
        $collection->delete();

        return response()->format([
            'html' => redirect()->route('collections.index'),
            'json' => new CollectionResource([]),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $collections = CampaignCollection::select('collections.*')
            ->leftJoin('campaign_collections', 'campaign_collections.collection_id', '=', 'collections.id')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'campaign_collections.campaign_id')
            ->groupBy('collections.id');

        /** @var QueryDataTable $datatable */
        $datatable = $dataTables->of($collections);
        return $datatable
            ->addColumn('actions', function (CampaignCollection $collection) {
                return [
                    'show' => route('campaigns.index', ['collection' => $collection]),
                    'edit' => route('collections.edit', $collection),
                    'destroy' => route('collections.destroy', $collection),
                ];
            })
            ->addColumn('name', function (CampaignCollection $collection) {
                return [
                    'url' => route('campaigns.index', ['collection' => $collection]),
                    'text' => $collection->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $query->where('collections.name', 'like', "%{$value}%");
            })
            ->addColumn('campaigns', function (CampaignCollection $collection) {
                $campaigns = [];

                foreach ($collection->campaigns as $campaign) {
                    $campaigns[] = html()->a(
                        href: route('campaigns.edit', $campaign),
                        contents: $campaign->name,
                    )->attribute('target', '_blank');
                }

                return $campaigns;
            })
            ->filterColumn('campaigns', function (Builder $query, $value) {
                $query->where('campaigns.name', 'like', "%{$value}%");
            })
            ->rawColumns(['name.text'])
            ->setRowId('id')
            ->make(true);
    }
}
