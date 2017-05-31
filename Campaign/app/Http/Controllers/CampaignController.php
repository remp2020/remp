<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\Contracts\SegmentContract;
use App\Contracts\TrackerContract;
use App\Http\Requests\CampaignRequest;
use App\Jobs\CacheSegmentJob;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Psy\Util\Json;
use View;
use Yajra\Datatables\Datatables;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;

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
        $campaign = new Campaign();
        $campaign->fill($request->all());

        $campaign->save();

        return redirect(route('campaigns.index'))->with('success', 'Campaign created');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign)
    {
        return view('campaigns.show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Campaign $campaign
     * @param SegmentContract $segmentContract
     * @return \Illuminate\Http\Response
     */
    public function edit(Campaign $campaign, SegmentContract $segmentContract)
    {
        $campaign->fill(old());

        $banners = Banner::all();
        $segments = $segmentContract->list();

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'banners' => $banners,
            'segments' => $segments,
        ]);
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
        $campaign->fill($request->all());
        $shouldCache = $campaign->isDirty('active');
        $campaign->save();

        if ($campaign->active && $shouldCache) {
            dispatch(new CacheSegmentJob($campaign->segment_id));
        }

        return redirect(route('campaigns.index'))->with('success', 'Campaign updated');
    }

    /**
     * @param Request $r
     * @param DimensionMap $dm
     * @param PositionMap $pm
     * @param AlignmentMap $am
     * @param SegmentContract $sc
     * @param TrackerContract $tc
     * @return \Illuminate\Http\JsonResponse
     */
    public function showtime(
        Request $r,
        DimensionMap $dm,
        PositionMap $pm,
        AlignmentMap $am,
        SegmentContract $sc,
        TrackerContract $tc
    ) {
        $campaign = Campaign::whereActive(true)->first();
        if (!$campaign) {
            return response()->json();
        }

        $data = \GuzzleHttp\json_decode($r->get('data'));
        $userId = isset($data->userId) ? $data->userId : null;

        if ($campaign->segment_id) {
            if (!$userId) {
                return response()->json();
            }
            if (!$sc->check($campaign->segment_id, $userId)) {
                return response()->json();
            }
        }

        $banner = $campaign->banner;
        if (!$banner) {
            return response()->json();
        }
        $positions = $pm->positions();
        $dimensions = $dm->dimensions();
        $alignments = $am->alignments();

        $expiresAt = Carbon::now()->addMinutes(10);
        Cache::put('key', 'value', $expiresAt);

        // following tracking is temporary and will be done from FE javascript directly to the Beam Tracker API.
        $referer = $r->headers->get("referer", "http://www.example.com");
        $userAgent = $r->headers->get("user-agent");
        $ip = $r->ip();
        $tc->event("campaign", "display", $referer, $ip, $userAgent, $userId, [
            "campaign_id" => $campaign->id,
            "banner_id" => $banner->id,
        ]);

        return response()
            ->jsonp($r->get('callback'), [
                'success' => true,
                'errors' => [],
                'data' => [
                    View::make('banners.preview', [
                        'banner' => $banner,
                        'positions' => [$banner->position => $positions[$banner->position]],
                        'dimensions' => [$banner->dimensions => $dimensions[$banner->dimensions]],
                        'alignments' => [$banner->text_align => $alignments[$banner->text_align]],
                    ])->render(),
                ],
            ]);
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
