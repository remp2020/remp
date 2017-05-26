<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\Contracts\SegmentContract;
use App\Http\Requests\CampaignRequest;
use Illuminate\Http\Request;
use Psy\Util\Json;
use Yajra\Datatables\Datatables;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('campaigns.index');
    }

    public function json(Datatables $dataTables)
    {
        $campaigns = Campaign::query();
        return $dataTables->of($campaigns)
            ->addColumn('actions', function(Campaign $campaign) {
                return Json::encode([
                    '_id' => $campaign->id,
                    'show' => route('campaigns.show', $campaign),
                    'edit' => route('campaigns.edit', $campaign) ,
                ]);
            })
            ->rawColumns(['actions'])
            ->setRowId('id')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param SegmentContract $segmentContract
     * @return \Illuminate\Http\Response
     */
    public function create(SegmentContract $segmentContract)
    {
        $campaign = new Campaign();
        $campaign->fill(old());

        $banners = Banner::all();
        $segments = $segmentContract->list();

        return view('campaigns.create', [
            'campaign' => $campaign,
            'banners' => $banners,
            'segments' => $segments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CampaignRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(CampaignRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function edit(Campaign $campaign)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CampaignRequest|Request $request
     * @param  \App\Campaign $campaign
     * @return \Illuminate\Http\Response
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function destroy(Campaign $campaign)
    {
        //
    }
}
