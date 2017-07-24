<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\CampaignSegment;
use App\Contracts\JournalContract;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentContract;
use App\Contracts\SegmentException;
use App\Contracts\TrackerContract;
use App\Http\Requests\CampaignRequest;
use App\Jobs\CacheSegmentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        return $dataTables->of($campaigns->with('segments'))
            ->addColumn('actions', function (Campaign $campaign) {
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
     * @param SegmentAggregator $segmentAggregator
     * @return \Illuminate\Http\Response
     */
    public function create(SegmentAggregator $segmentAggregator)
    {
        $campaign = new Campaign();
        $campaign->fill(old());
        $selectedSegments = collect(old('segments'));

        $banners = Banner::all();
        try {
            $segments = $segmentAggregator->list();
        } catch (SegmentException $e) {
            $segments = new Collection();
            flash('Unable to fetch list of segments, please check the application configuration.')->error();
            \Log::error($e->getMessage());
        }

        return view('campaigns.create', [
            'campaign' => $campaign,
            'banners' => $banners,
            'segments' => $segments,
            'selectedSegments' => $selectedSegments,
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

        foreach ($request->get('segments') as $r) {
            /** @var CampaignSegment $rule */
            $rule = new CampaignSegment();
            $rule->code = $r['code'];
            $rule->provider = $r['provider'];
            $rule->campaign_id = $campaign->id;
            $rule->save();
        }

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
     * @param SegmentAggregator $segmentAggregator
     * @return \Illuminate\Http\Response
     */
    public function edit(Campaign $campaign, SegmentAggregator $segmentAggregator)
    {
        $campaign->fill(old());
        $banners = Banner::all();

        try {
            $segments = $segmentAggregator->list();
        } catch (SegmentException $e) {
            $segments = new Collection();
            flash('Unable to fetch list of segments, please check the application configuration.')->error();
            \Log::error($e->getMessage());
        }

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

        foreach ($request->get('segments') as $r) {
            /** @var CampaignSegment $rule */
            $rule = CampaignSegment::findOrNew($r['id']);
            $rule->code = $r['code'];
            $rule->provider = $r['provider'];
            $rule->campaign_id = $campaign->id;
            $rule->save();
        }

        CampaignSegment::destroy($request->get('removedSegments'));

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
     * @param SegmentAggregator $sa
     * @param TrackerContract $tc
     * @param JournalContract $jc
     * @return \Illuminate\Http\JsonResponse
     */
    public function showtime(
        Request $r,
        DimensionMap $dm,
        PositionMap $pm,
        AlignmentMap $am,
        SegmentAggregator $sa,
        TrackerContract $tc,
        JournalContract $jc
    ) {
        // validation

        /** @var Campaign $campaign */
        $campaign = Campaign::whereActive(true)->first();
        if (!$campaign) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => true,
                    'data' => [],
                ]);
        }

        $data = \GuzzleHttp\json_decode($r->get('data'));
        $beamToken = $data->beamToken ?? null;
        if (!$beamToken) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => ['beamToken is required and missing'],
                ]);
        }
        $url = $data->url ?? null;
        if (!$url) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => ['url is required and missing'],
                ]);
        }

        // segment

        $userId = $data->userId ?? null;
        if (!$userId) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => [],
                ])
                ->setStatusCode(400);
        }
        foreach ($campaign->segments as $campaignSegment) {
            if (!$sa->check($campaignSegment, $userId)) {
                return response()
                    ->jsonp($r->get('callback'), [
                        'success' => true,
                        'data' => [],
                    ]);
            }
        }

        $banner = $campaign->banner;
        if (!$banner) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => ["active campaign [{$campaign->uuid}] has no banner set"],
                ]);
        }

        $positions = $pm->positions();
        $dimensions = $dm->dimensions();
        $alignments = $am->alignments();

        // following tracking is temporary and will be done from FE javascript directly to the Beam Tracker API.
        $userAgent = $r->headers->get("user-agent");
        $ip = $r->ip();
        $tc->event($beamToken, "campaign", "display", $url, $ip, $userAgent, $userId, [
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
