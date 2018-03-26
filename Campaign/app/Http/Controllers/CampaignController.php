<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Campaign;
use App\CampaignSegment;
use App\Contracts\SegmentAggregator;
use App\Contracts\SegmentException;
use App\Country;
use App\Http\Request;
use App\Http\Requests\CampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Schedule;
use Cache;
use Carbon\Carbon;
use GeoIp2;
use HTML;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use View;
use Yajra\Datatables\Datatables;
use App\Models\Dimension\Map as DimensionMap;
use App\Models\Position\Map as PositionMap;
use App\Models\Alignment\Map as AlignmentMap;
use DeviceDetector\DeviceDetector;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->format([
            'html' => view('campaigns.index'),
            'json' => CampaignResource::collection(Campaign::paginate()),
        ]);
    }

    public function json(Datatables $dataTables)
    {
        $campaigns = Campaign::select()
            ->with(['banner', 'altBanner', 'segments', 'countries'])
            ->get();

        return $dataTables->of($campaigns)
            ->addColumn('actions', function (Campaign $campaign) {
                return [
                    'edit' => route('campaigns.edit', $campaign),
                    'copy' => route('campaigns.copy', $campaign),
                ];
            })
            ->addColumn('name', function (Campaign $campaign) {
                return Html::linkRoute('campaigns.edit', $campaign->name, $campaign);
            })
            ->addColumn('banner', function (Campaign $campaign) {
                return Html::linkRoute('banners.edit', $campaign->banner->name, $campaign->banner);
            })
            ->addColumn('alt_banner', function (Campaign $campaign) {
                if (!$campaign->altBanner) {
                    return null;
                }
                return Html::linkRoute('banners.edit', $campaign->altBanner->name, $campaign->altBanner);
            })
            ->addColumn('segments', function (Campaign $campaign) {
                return implode(' ', $campaign->segments->pluck('code')->toArray());
            })
            ->addColumn('countries', function (Campaign $campaign) {
                return implode(' ', $campaign->countries->pluck('name')->toArray());
            })
            ->addColumn('active', function (Campaign $campaign) {
                return view('campaigns.partials.activeToggle', [
                    'id' => $campaign->id,
                    'active' => $campaign->active
                ])->render();
            })
            ->addColumn('devices', function (Campaign $campaign) {
                return count($campaign->devices) == count($campaign->getAllDevices()) ? 'all' : implode(' ', $campaign->devices);
            })
            ->rawColumns(['actions', 'active', 'signed_in', 'once_per_session'])
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

        $segments = $segmentAggregator->list();
        foreach ($segmentAggregator->getErrors() as $error) {
            flash($error)->error();
            Log::error($error);
        }

        return view('campaigns.create', [
            'campaign' => $campaign,
            'banners' => Banner::all(),
            'availableCountries' => Country::all(),
            'segments' => $segments,
            'selectedSegments' => $selectedSegments,
        ]);
    }

    public function copy(Campaign $sourceCampaign, SegmentAggregator $segmentAggregator)
    {
        $sourceCampaign->load('banner', 'altBanner', 'segments', 'countries');
        $campaign = $sourceCampaign->replicate();

        $segments = $segmentAggregator->list();
        foreach ($segmentAggregator->getErrors() as $error) {
            flash($error)->error();
            Log::error($error);
        }

        flash(sprintf('Form has been pre-filled with data from campaign "%s"', $sourceCampaign->name))->info();

        return view('campaigns.create', [
            'campaign' => $campaign,
            'banners' => Banner::all(),
            'availableCountries' => Country::all(),
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
        $campaign->banner_id = $request->get('banner_id');
        $campaign->alt_banner_id = $request->get('alt_banner_id');

        $campaign->countries()->sync($this->processCountries($request));

        foreach ($request->get('segments', []) as $r) {
            /** @var CampaignSegment $campaignSegment */
            $campaignSegment = new CampaignSegment();
            $campaignSegment->code = $r['code'];
            $campaignSegment->provider = $r['provider'];
            $campaignSegment->campaign_id = $campaign->id;
            $campaignSegment->save();
        }

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'campaigns.index',
                    self::FORM_ACTION_SAVE => 'campaigns.edit',
                ],
                $campaign
            )->with('success', sprintf('Campaign [%s] was created', $campaign->name)),
            'json' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign)
    {
        return response()->format([
            'html' => view('campaigns.show', [
                'campaign' => $campaign,
            ]),
            'json' => new CampaignResource($campaign),
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

        try {
            $segments = $segmentAggregator->list();
        } catch (SegmentException $e) {
            $segments = new Collection();
            flash('Unable to fetch list of segments, please check the application configuration.')->error();
            Log::error($e->getMessage());
        }

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'availableCountries' => Country::all(),
            'banners' => Banner::all(),
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
        $campaign->save();
        $campaign->banner_id = $request->get('banner_id');
        $campaign->alt_banner_id = $request->get('alt_banner_id');

        $campaign->countries()->sync($this->processCountries($request));

        foreach ($request->get('segments', []) as $r) {
            /** @var CampaignSegment $campaignSegment */
            $campaignSegment = CampaignSegment::findOrNew($r['id']);
            $campaignSegment->code = $r['code'];
            $campaignSegment->provider = $r['provider'];
            $campaignSegment->campaign_id = $campaign->id;
            $campaignSegment->save();
        }

        CampaignSegment::destroy($request->get('removedSegments'));

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                        self::FORM_ACTION_SAVE_CLOSE => 'campaigns.index',
                        self::FORM_ACTION_SAVE => 'campaigns.edit',
                    ],
                $campaign
            )->with('success', sprintf('Campaign [%s] was updated', $campaign->name)),
            'json' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Toggle campaign status and activate / deactivate current schedule.
     *
     * If campaign is not active, activate it:
     * - change active flag to true,
     * - create new schedule with status executed (it wasn't planned).
     *
     * If campaign is active, deactivate it:
     * - change active flag to false,
     * - stop all running or planned schedules.
     *
     * @param Campaign $campaign
     * @return JsonResponse
     */
    public function toggleActive(Campaign $campaign): JsonResponse
    {
        if (!$campaign->active) {
            $campaign->active = true;

            $schedule = new Schedule();
            $schedule->campaign_id = $campaign->id;
            $schedule->start_time = Carbon::now();
            $schedule->status = Schedule::STATUS_EXECUTED;
            $schedule->save();
        } else {
            $campaign->active = false;

            /** @var Schedule $schedule */
            foreach ($campaign->schedules()->runningOrPlanned()->get() as $schedule) {
                $schedule->status = Schedule::STATUS_STOPPED;
                $schedule->save();
            }
        }

        $campaign->save();

        return response()->json([
            'active' => $campaign->active
        ]);
    }

    /**
     * Processes campaign $request and returns countries array ready to sync with campaign_country pivot table
     *
     * @param CampaignRequest $request
     * @return array
     */
    private function processCountries(CampaignRequest $request): array
    {
        $blacklist = $request->get('countries_blacklist');
        $countries = [];
        foreach ($request->get('countries', []) as $cid) {
            $countries[$cid] = ['blacklisted' => (bool) $blacklist];
        }
        return $countries;
    }

    /**
     * @param Request $r
     * @param DimensionMap $dm
     * @param PositionMap $pm
     * @param AlignmentMap $am
     * @param SegmentAggregator $sa
     * @return JsonResponse
     */
    public function showtime(
        Request $r,
        DimensionMap $dm,
        PositionMap $pm,
        AlignmentMap $am,
        SegmentAggregator $sa,
        GeoIp2\Database\Reader $geoIPreader,
        DeviceDetector $dd
    ) {
        // validation

        $data = \GuzzleHttp\json_decode($r->get('data'));
        $url = $data->url ?? null;
        if (!$url) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => ['url is required and missing'],
                ]);
        }

        $userId = null;
        if (isset($data->userId) || !empty($data->userId)) {
            $userId = $data->userId;
        }

        $browserId = null;
        if (isset($data->browserId) || !empty($data->browserId)) {
            $browserId = $data->browserId;
        }
        if (!$browserId) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => false,
                    'errors' => ['browserId is required and missing'],
                ])
                ->setStatusCode(400);
        }

        if (isset($data->cache)) {
            $sa->setCache($data->cache);
        }

        $campaignIds = Cache::get(Campaign::ACTIVE_CAMPAIGN_IDS, []);
        if (count($campaignIds) == 0) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => true,
                    'data' => [],
                    'providerData' => $sa->getProviderData(),
                ]);
        }

        /** @var Campaign $campaign */
        $positions = $pm->positions();
        $dimensions = $dm->dimensions();
        $alignments = $am->alignments();
        $displayedCampaigns = [];

        foreach ($campaignIds as $campaignId) {
            $campaign = Cache::tags(Campaign::CAMPAIGN_TAG)->get($campaignId);
            $running = false;
            foreach ($campaign->schedules as $schedule) {
                if ($schedule->isRunning()) {
                    $running = true;
                    break;
                }
            }
            if (!$running) {
                continue;
            }

            // banner
            $bannerVariantA = $campaign->banner ?? false;
            if (!$bannerVariantA) {
                Log::error("Active campaign [{$campaign->uuid}] has no banner set");
                continue;
            }

            $banner = null;
            $bannerVariantB = $campaign->altBanner ?? false;
            if (!$bannerVariantB) {
                // only one variant of banner, so set it
                $banner = $bannerVariantA;
            } else {
                // there are two variants
                // find banner previously displayed to user
                $bannerId = null;
                $campaignsBanners = $data->campaignsBanners ?? false;
                if ($campaignsBanners && isset($campaignsBanners->{$campaign->uuid})) {
                    $bannerId = $campaignsBanners->{$campaign->uuid}->bannerId ?? null;
                }

                if ($bannerId !== null) {
                    // check if displayed banner is one of existing variants
                    switch ($bannerId) {
                        case $bannerVariantA->uuid:
                            $banner = $bannerVariantA;
                            break;
                        case $bannerVariantB->uuid:
                            $banner = $bannerVariantB;
                            break;
                    }
                }

                // banner still not set, choose random variant
                if ($banner === null) {
                    $banner = rand(0, 1) ? $bannerVariantA : $bannerVariantB;
                }
            }

            // check if campaign is set to be seen only once per session
            // and check campaign UUID against list of campaigns seen by user
            $campaignsSeen = $data->campaignsSeen ?? false;
            if ($campaign->once_per_session && $campaignsSeen) {
                $seen = false;
                foreach ($campaignsSeen as $campaignSeen) {
                    if ($campaignSeen->campaignId === $campaign->uuid) {
                        $seen = true;
                        break;
                    }
                }
                if ($seen) {
                    continue;
                }
            }

            // signed in state
            if (isset($campaign->signed_in) && $campaign->signed_in !== boolval($userId)) {
                continue;
            }

            // device rules
            $dd->setUserAgent($data->userAgent);
            $dd->parse();

            if (!in_array(Campaign::DEVICE_MOBILE, $campaign->devices) && $dd->isMobile()) {
                continue;
            }

            if (!in_array(Campaign::DEVICE_DESKTOP, $campaign->devices) && $dd->isDesktop()) {
                continue;
            }

            // country rules
            if (!$campaign->countries->isEmpty()) {
                // load country ISO code based on IP
                try {
                    $record = $geoIPreader->country($r->ip());
                    $countryCode = $record->country->isoCode;
                } catch (\MaxMind\Db\Reader\InvalidDatabaseException | GeoIp2\Exception\AddressNotFoundException $e) {
                    Log::error("Unable to load country for campaign [{$campaign->uuid}] with country rules: " . $e->getMessage());
                    continue;
                }
                if (is_null($countryCode)) {
                    Log::error("Unable to load country for campaign [{$campaign->uuid}] with country rules");
                    continue;
                }

                // check against white / black listed countries

                if (!$campaign->countriesBlacklist->isEmpty() && $campaign->countriesBlacklist->contains('iso_code', $countryCode)) {
                    continue;
                }
                if (!$campaign->countriesWhitelist->isEmpty() && !$campaign->countriesWhitelist->contains('iso_code', $countryCode)) {
                    continue;
                }
            }

            // segment
            foreach ($campaign->segments as $campaignSegment) {
                $campaignSegment->setRelation('campaign', $campaign); // setting this manually to avoid DB query

                if ($userId) {
                    if (!$sa->checkUser($campaignSegment, strval($userId))) {
                        continue 2;
                    }
                } else {
                    if (!$sa->checkBrowser($campaignSegment, strval($browserId))) {
                        continue 2;
                    }
                }
            }

            // pageview rules
            if ($campaign->pageview_rules !== null) {
                foreach ($campaign->pageview_rules as $rule) {
                    if (!$rule['num'] || !$rule['rule']) {
                        continue;
                    }

                    switch ($rule['rule']) {
                        case Campaign::PAGEVIEW_RULE_EVERY:
                            if ($data->pageviewCount % $rule['num'] !== 0) {
                                continue 3;
                            }
                            break;
                        case Campaign::PAGEVIEW_RULE_SINCE:
                            if ($data->pageviewCount < $rule['num']) {
                                continue 3;
                            }
                            break;
                        case Campaign::PAGEVIEW_RULE_BEFORE:
                            if ($data->pageviewCount >= $rule['num']) {
                                continue 3;
                            }
                            break;
                    }
                }
            }

            //render
            $displayedCampaigns[] = View::make('banners.preview', [
                'banner' => $banner,
                'campaign' => $campaign,
                'positions' => $positions,
                'dimensions' => $dimensions,
                'alignments' => $alignments,
            ])->render();
        }

        if (empty($displayedCampaigns)) {
            return response()
                ->jsonp($r->get('callback'), [
                    'success' => true,
                    'data' => [],
                    'providerData' => $sa->getProviderData(),
                ]);
        }

        return response()
            ->jsonp($r->get('callback'), [
                'success' => true,
                'errors' => [],
                'data' => $displayedCampaigns,
                'providerData' => $sa->getProviderData(),
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
