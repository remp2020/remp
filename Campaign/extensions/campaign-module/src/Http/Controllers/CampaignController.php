<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Banner;
use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignSegment;
use Remp\CampaignModule\CampaignCollection;
use Remp\CampaignModule\Contracts\SegmentAggregator;
use Remp\CampaignModule\Contracts\SegmentException;
use Remp\CampaignModule\Country;
use Remp\CampaignModule\Http\Requests\CampaignRequest;
use Remp\CampaignModule\Http\Resources\CampaignResource;
use Remp\CampaignModule\Http\Showtime\ControllerShowtimeResponse;
use Remp\CampaignModule\Http\Showtime\Showtime;
use Remp\CampaignModule\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class CampaignController extends Controller
{
    private $beamJournalConfigured;

    private $showtime;

    public function __construct(Showtime $showtime)
    {
        $this->beamJournalConfigured = !empty(config('services.remp.beam.segments_addr'));
        $this->showtime = $showtime;
    }

    public function index(SegmentAggregator $segmentAggregator, CampaignCollection $collection = null)
    {
        $availableSegments = $this->getAllSegments($segmentAggregator)->pluck('name', 'code');
        $segments = CampaignSegment::get()->mapWithKeys(function ($item) use ($availableSegments) {
            return [$item->code => $availableSegments->get($item->code) ?? $item->code];
        });
        $variants = CampaignBanner::with('banner')
            ->whereNotNull('banner_id')
            ->get()
            ->pluck('banner.name', 'banner.id');

        return response()->format([
            'html' => view('campaign::campaigns.index', [
                'beamJournalConfigured' => $this->beamJournalConfigured,
                'segments' => $segments,
                'variants' => $variants,
                'collection' => $collection,
            ]),
            'json' => CampaignResource::collection(Campaign::paginate()),
        ]);
    }

    public function json(Datatables $dataTables, SegmentAggregator $segmentAggregator, CampaignCollection $collection = null)
    {
        $campaigns = Campaign::select('campaigns.*')
            ->with(['segments', 'countries', 'collections', 'campaignBanners', 'campaignBanners.banner', 'schedules']);

        if ($collection) {
            $campaigns->whereHas('collections', function ($query) use ($collection) {
                $query->where('collection_id', $collection->id);
            });
        }

        $segments = $this->getAllSegments($segmentAggregator)->pluck('name', 'code');

        /** @var QueryDataTable $datatable */
        $datatable = $dataTables->of($campaigns);
        return $datatable
            ->addColumn('actions', function (Campaign $campaign) use ($collection) {
                return [
                    'edit' => route('campaigns.edit', ['campaign' => $campaign, 'collection' => $collection]),
                    'copy' => route('campaigns.copy', ['sourceCampaign' => $campaign, 'collection' => $collection]),
                    'stats' => route('campaigns.stats', ['campaign' => $campaign, 'collection' => $collection]),
                    'compare' => route('comparison.add', ['campaign' => $campaign, 'collection' => $collection]),
                ];
            })
            ->addColumn('name', function (Campaign $campaign) use ($collection) {
                return [
                    'url' => route('campaigns.edit', ['campaign' => $campaign, 'collection' => $collection]),
                    'text' => $campaign->name,
                ];
            })
            ->filterColumn('name', function (Builder $query, $value) {
                $query->where('campaigns.name', 'like', "%{$value}%");
            })
            ->filterColumn('public_id', function (Builder $query, $value) {
                $query->where('campaigns.public_id', $value);
            })
            ->addColumn('variants', function (Campaign $campaign) {
                $data = $campaign->campaignBanners->all();
                $variants = [];

                /** @var CampaignBanner $variant */
                foreach ($data as $variant) {
                    $proportion = $variant->proportion;
                    if ($proportion === 0) {
                        continue;
                    }

                    // handle control group
                    if ($variant->control_group === 1) {
                        $variants[] = "Control Group&nbsp;({$proportion}%)";
                        continue;
                    }

                    // handle variants with banner
                    $link = html()->a(
                        href: route('banners.edit', $variant->banner_id),
                        contents: $variant->banner->name
                    );

                    $variants[] = "{$link}&nbsp;({$proportion}%)";
                }

                return $variants;
            })
            ->filterColumn('variants', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('campaign_banners')
                    ->select(['campaign_banners.campaign_id'])
                    ->whereIn('campaign_banners.banner_id', $values)
                    ->where('campaign_banners.proportion', '>', 0);
                $query->whereIn('campaigns.id', $filterQuery);
            })
            ->addColumn('segments', function (Campaign $campaign) use ($segments) {
                $segmentNames = [];

                $exclusiveIcon = '<i class="zmdi zmdi-eye-off" title="User must not be member of segment to see the campaign."></i>';
                $inclusiveIcon = '<i class="zmdi zmdi-eye primary-color" title="User needs to be member of segment to see the campaign."></i>';

                foreach ($campaign->segments as $segment) {
                    $icon = $segment->inclusive ? $inclusiveIcon : $exclusiveIcon;

                    if ($segments->get($segment->code)) {
                        $segmentNames[] = "{$icon} <span title='{$segment->code}'>{$segments->get($segment->code)}</span></em>";
                    } else {
                        $segmentNames[] = "{$icon} <span title='{$segment->code}'>{$segment->code}</span></em>";
                    }
                }

                return $segmentNames;
            })
            ->addColumn('collections', function (Campaign $campaign) {
                $collections = [];
                foreach ($campaign->collections as $collection) {
                    $collections[] = html()->a(
                        href: route('campaigns.index', ['collection' => $collection->id]),
                        contents: $collection->name,
                    )->attribute('target', '_blank');
                }

                return $collections;
            })
            ->filterColumn('segments', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('campaign_segments')
                    ->select(['campaign_segments.campaign_id'])
                    ->whereIn('campaign_segments.code', $values);
                $query->whereIn('campaigns.id', $filterQuery);
            })
            ->addColumn('countries', function (Campaign $campaign) {
                return implode(' ', $campaign->countries->pluck('name')->toArray());
            })
            ->addColumn('active', function (Campaign $campaign) {
                $active = $campaign->active;
                return view('campaign::campaigns.partials.activeToggle', [
                    'id' => $campaign->id,
                    'active' => $active,
                    'title' => $active ? 'Deactivate campaign' : 'Activate campaign'
                ])->render();
            })
            ->orderColumn('active', function (Builder $query, $order) {
                $query
                    ->withAggregate(['schedules' => function ($scheduleQuery) {
                        $scheduleQuery->running();
                    }], 'id')
                    ->orderBy(DB::raw('ISNULL(schedules_id)'), $order);
            })
            ->addColumn('is_running', function (Campaign $campaign) {
                foreach ($campaign->schedules as $schedule) {
                    if ($schedule->isRunning()) {
                        return true;
                    }
                }
                return false;
            })
            ->addColumn('signed_in', function (Campaign $campaign) {
                return $campaign->signedInLabel();
            })
            ->addColumn('devices', function (Campaign $campaign) {
                return count($campaign->devices) == count($campaign->getAllDevices()) ? 'all' : implode(' ', $campaign->devices);
            })
            ->rawColumns(['name.text', 'actions', 'active', 'signed_in', 'once_per_session', 'variants', 'is_running', 'segments'])
            ->setRowId('id')
            ->make(true);
    }

    public function create(SegmentAggregator $segmentAggregator, CampaignCollection $collection = null)
    {
        $campaign = new Campaign();

        [
            $campaign,
            $bannerId,
            $variants,
            $selectedCountries,
            $selectedLanguages,
            $countriesBlacklist
        ] = $this->processOldCampaign($campaign, old());

        return view('campaign::campaigns.create', [
            'campaign' => $campaign,
            'bannerId' => $bannerId,
            'variants' => $variants,
            'selectedCountries' => $selectedCountries,
            'selectedLanguages' => $selectedLanguages,
            'countriesBlacklist' => $countriesBlacklist,
            'banners' => Banner::all(),
            'availableCountries' => Country::all(),
            'availableLanguages' => Campaign::getAvailableLanguages(),
            'segments' => $this->getAllSegments($segmentAggregator),
            'collection' => $collection,
        ]);
    }

    public function copy(Campaign $sourceCampaign, SegmentAggregator $segmentAggregator, CampaignCollection $collection = null)
    {
        $sourceCampaign->load('banners', 'campaignBanners', 'segments', 'countries');
        /** @var Campaign $campaign */
        $campaign = $sourceCampaign->replicate();

        flash(sprintf('Form has been pre-filled with data from campaign "%s"', $sourceCampaign->name))->info();

        [
            $campaign,
            $bannerId,
            $variants,
            $selectedCountries,
            $selectedLanguages,
            $countriesBlacklist
        ] = $this->processOldCampaign($campaign, old());

        return view('campaign::campaigns.create', [
            'campaign' => $campaign,
            'bannerId' => $bannerId,
            'variants' => $variants,
            'selectedCountries' => $selectedCountries,
            'selectedLanguages' => $selectedLanguages,
            'countriesBlacklist' => $countriesBlacklist,
            'banners' => Banner::all(),
            'availableCountries' => Country::all()->keyBy("iso_code"),
            'availableLanguages' => Campaign::getAvailableLanguages(),
            'segments' => $this->getAllSegments($segmentAggregator),
            'collection' => $collection,
        ]);
    }

    public function validateForm(CampaignRequest $request)
    {
        return response()->json(false);
    }

    public function store(CampaignRequest $request, CampaignCollection $collection = null)
    {
        $campaign = new Campaign();
        $requestData = $request->all();

        // if copying data from existing campaign, ignore 'removed_segments',
        // since they are CampaignSegments referenced from the old campaign
        unset($requestData['removed_segments']);

        $this->saveCampaign($campaign, $requestData, $collection);

        $message = ['success' => sprintf('Campaign [%s] was created', $campaign->name)];

        // (de)activate campaign (based on flag or new schedule)
        $message['warning'] = $this->processCampaignActivation(
            $campaign,
            $request->get('activation_mode'),
            $request->get('active', false),
            $request->get('new_schedule_start_time'),
            $request->get('new_schedule_end_time')
        );

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                    self::FORM_ACTION_SAVE_CLOSE => 'campaigns.index',
                    self::FORM_ACTION_SAVE => 'campaigns.edit',
                ],
                ['campaign' => $campaign, 'collection' => $collection]
            )->with($message),
            'json' => new CampaignResource($campaign),
        ]);
    }

    public function show(Campaign $campaign)
    {
        return response()->format([
            'html' => view('campaign::campaigns.show', [
                'campaign' => $campaign,
            ]),
            'json' => new CampaignResource($campaign),
        ]);
    }

    public function edit(Campaign $campaign, SegmentAggregator $segmentAggregator, CampaignCollection $collection = null)
    {
        [
            $campaign,
            $bannerId,
            $variants,
            $selectedCountries,
            $selectedLanguages,
            $countriesBlacklist
        ] = $this->processOldCampaign($campaign, old());

        return view('campaign::campaigns.edit', [
            'campaign' => $campaign,
            'bannerId' => $bannerId,
            'variants' => $variants,
            'selectedCountries' => $selectedCountries,
            'selectedLanguages' => $selectedLanguages,
            'countriesBlacklist' => $countriesBlacklist,
            'banners' => Banner::all(),
            'availableCountries' => Country::all()->keyBy("iso_code"),
            'availableLanguages' => Campaign::getAvailableLanguages(),
            'segments' => $this->getAllSegments($segmentAggregator),
            'collection' => $collection,
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign, CampaignCollection $collection = null)
    {
        $this->saveCampaign($campaign, $request->all(), $collection);

        $message = ['success' => sprintf('Campaign [%s] was updated.', $campaign->name)];

        // (de)activate campaign (based on flag or new schedule)
        $message['warning'] = $this->processCampaignActivation(
            $campaign,
            $request->get('activation_mode'),
            $request->get('active', false),
            $request->get('new_schedule_start_time'),
            $request->get('new_schedule_end_time')
        );

        return response()->format([
            'html' => $this->getRouteBasedOnAction(
                $request->get('action'),
                [
                        self::FORM_ACTION_SAVE_CLOSE => 'campaigns.index',
                        self::FORM_ACTION_SAVE => 'campaigns.edit',
                    ],
                ['campaign' => $campaign, 'collection' => $collection]
            )->with($message),
            'json' => new CampaignResource($campaign),
        ]);
    }

    /**
     * (De)Activate campaign (based on flag or new schedule).
     *
     * If `$activationMode` is 'activate-schedule' and new schedule has start time, create new schedule.
     * Otherwise activate / deactivate schedules - action based on provided `$activate` flag.
     *
     * @param $campaign
     * @param $activationMode
     * @param null $activate
     * @param null $newScheduleStartTime
     * @param null $newScheduleEndTime
     * @return null|string
     */
    private function processCampaignActivation(
        $campaign,
        $activationMode,
        $activate = null,
        $newScheduleStartTime = null,
        $newScheduleEndTime = null
    ): ?string {
        if ($activationMode === 'activate-schedule'
            && !is_null($newScheduleStartTime)) {
            $schedule = new Schedule();
            $schedule->campaign_id = $campaign->id;
            $schedule->start_time = $newScheduleStartTime;
            $schedule->end_time = $newScheduleEndTime;
            $schedule->save();
            return sprintf("Schedule with start time '%s' added", Carbon::parse($schedule->start_time)->toDayDateTimeString());
        } else {
            return $this->toggleSchedules($campaign, $activate);
        }
    }

    /**
     * Toggle campaign status - create or stop schedules.
     *
     * If campaign is not active, activate it:
     * - create new schedule with status executed (it wasn't planned).
     *
     * If campaign is active, deactivate it:
     * - stop all running or planned schedules.
     *
     * @param Campaign $campaign
     * @return JsonResponse
     */
    public function toggleActive(Campaign $campaign): JsonResponse
    {
        $activate = false;
        if (!$campaign->active) {
            $activate = true;
        }

        $this->toggleSchedules($campaign, $activate);

        // TODO: maybe add message from toggleSchedules to response?
        return response()->json([
            'active' => $campaign->active
        ]);
    }

    /**
     * Toggle schedules of campaign.
     *
     * When `activate` argument is not passed, no change is triggered.
     *
     * @param Campaign $campaign
     * @param null|boolean $activate
     * @return null|string Returns message about schedules state change.
     */
    private function toggleSchedules(Campaign $campaign, $activate = null): ?string
    {
        // do not change schedules when there is no `activate` order
        if (is_null($activate)) {
            return null;
        }

        $schedulesChangeMsg = null;
        if ($activate) {
            $activated = $this->startCampaignSchedule($campaign);
            if ($activated) {
                $schedulesChangeMsg = "Campaign was activated and is running.";
            }
        } else {
            $stopped = $this->stopCampaignSchedule($campaign);
            if ($stopped) {
                $schedulesChangeMsg = "Campaign was deactivated, all schedules were stopped.";
            }
        }

        return $schedulesChangeMsg;
    }


    /**
     * If no campaign's schedule is running, start new one.
     *
     * @param Campaign $campaign
     * @return bool Returns true if new schedule was added.
     */
    private function startCampaignSchedule(Campaign $campaign)
    {
        $activated = false;
        if (!$campaign->schedules()->running()->count()) {
            $schedule = new Schedule();
            $schedule->campaign_id = $campaign->id;
            $schedule->start_time = Carbon::now();
            $schedule->status = Schedule::STATUS_EXECUTED;
            $schedule->save();
            $activated = true;
        }

        return $activated;
    }

    /**
     * Stop all campaign schedules.
     *
     * @param Campaign $campaign
     * @return bool Returns true if any schedule was running and was stopped.
     */
    private function stopCampaignSchedule(Campaign $campaign): bool
    {
        $stopped = false;
        /** @var Schedule $schedule */
        foreach ($campaign->schedules()->runningOrPlanned()->get() as $schedule) {
            $schedule->status = Schedule::STATUS_STOPPED;
            $schedule->end_time = Carbon::now();
            $schedule->save();
            $stopped = true;
        }
        return $stopped;
    }

    /**
     * Returns countries array ready to sync with campaign_country pivot table
     *
     * @param array $countries
     * @param bool $blacklist
     * @return array
     */
    private function processCountries(array $countries, bool $blacklist): array
    {
        $processed = [];

        foreach ($countries as $cid) {
            $processed[$cid] = ['blacklisted' => $blacklist];
        }

        return $processed;
    }


    /**
     * @param Request                    $request
     * @param Showtime                   $showtime
     * @param ControllerShowtimeResponse $controllerShowtimeResponse
     *
     * @return JsonResponse
     */
    public function showtime(
        Request $request,
        Showtime $showtime,
        ControllerShowtimeResponse $controllerShowtimeResponse
    ) {
        $showtime->setRequest($request);
        $data = $request->get('data');
        $callback = $request->get('callback');

        if ($data === null || $callback === null) {
            return response()->json(['errors' => ['invalid request, data or callback params missing']], 400);
        }

        if (!empty($request->getPreferredLanguage())) {
            $showtime->getShowtimeConfig()->setAcceptLanguage($request->getPreferredLanguage());
        }

        return $showtime->showtime($data, $callback, $controllerShowtimeResponse);
    }

    public function saveCampaign(Campaign $campaign, array $data, CampaignCollection $collection = null)
    {
        if (empty($data['operating_systems'])) {
            $data['operating_systems'] = null;
        }

        $campaign->fill($data);
        $campaign->save();

        if (!empty($data['variants_to_remove'])) {
            $campaign->removeVariants($data['variants_to_remove']);
        }

        $campaign->storeOrUpdateVariants($data['variants']);

        $campaign->countries()->sync(
            $this->processCountries(
                $data['countries'] ?? [],
                (bool)$data['countries_blacklist']
            )
        );

        $segments = $data['segments'] ?? [];

        foreach ($segments as $segment) {
            CampaignSegment::firstOrCreate([
                'campaign_id' => $campaign->id,
                'code' => $segment['code'],
                'provider' => $segment['provider'],
                'inclusive' => $segment['inclusive']
            ]);
        }

        if (isset($data['removed_segments'])) {
            CampaignSegment::destroy($data['removed_segments']);
        }

        if ($collection !== null && !$campaign->collections->contains($collection)) {
            $campaign->collections()->attach($collection);
        }
    }

    public function processOldCampaign(Campaign $campaign, array $data)
    {
        $campaign->fill($data);

        // parse old segments data
        $segments = [];
        $segmentsData = isset($data['segments']) ? $data['segments'] : $campaign->segments->toArray();
        $removedSegments = isset($data['removedSegments']) ? $data['removedSegments'] : [];

        foreach ($segmentsData as $segment) {
            if (is_null($segment['id']) || !array_search($segment['id'], $removedSegments)) {
                $segments[] = $campaign->segments()->make($segment);
            }
        }
        $campaign->setRelation('segments', collect($segments));

        // parse selected countries
        $countries = $campaign->countries->toArray();
        $selectedCountries = $data['countries'] ?? array_map(function ($country) {
            return $country['iso_code'];
        }, $countries);

        // countries blacklist?
        $blacklisted = 0;
        foreach ($countries as $country) {
            $blacklisted = (int)$country['pivot']['blacklisted'];
        }

        // main banner
        if (array_key_exists('banner_id', $data)) {
            $bannerId = $data['banner_id'];
        } elseif (!$campaign->campaignBanners->isEmpty()) {
            $bannerId = optional($campaign->campaignBanners[0])->banner_id;
        } else {
            $bannerId = optional($campaign->campaignBanners()->first())->banner_id;
        }

        // variants
        if (array_key_exists('variants', $data)) {
            $variants = $data['variants'];
        } elseif (!$campaign->campaignBanners->isEmpty()) {
            $variants = $campaign->campaignBanners;
        } else {
            $variants = $campaign->campaignBanners()
                                ->with('banner')
                                ->get();
        }

        return [
            $campaign,
            $bannerId,
            $variants,
            $selectedCountries,
            $campaign->languages,
            $data['countries_blacklist'] ?? $blacklisted
        ];
    }

    public function getAllSegments(SegmentAggregator $segmentAggregator): Collection
    {
        try {
            $segments = $segmentAggregator->list();
        } catch (SegmentException $e) {
            $segments = new CampaignCollection();
            flash('Unable to fetch list of segments, please check the application configuration.')->error();
            Log::error($e->getMessage());
        }

        foreach ($segmentAggregator->getErrors() as $error) {
            flash(nl2br($error))->error();
            Log::error($error);
        }

        return $segments;
    }

    public function stats(
        Campaign $campaign,
        Request $request
    ) {
        /** @var CampaignBanner[] $variants */
        $variants = $campaign->campaignBanners()->withTrashed()->with("banner")->get();
        $from = $request->input('from', 'today - 30 days');
        $to = $request->input('to', 'now');

        $variantBannerLinks = [];
        $variantBannerTexts = [];
        foreach ($variants as $variant) {
            if (!$variant->banner) {
                continue;
            }
            $variantBannerLinks[$variant->uuid] = route('banners.show', ['banner' => $variant->banner]);
            $variantBannerTexts[$variant->uuid] = $variant->banner->getTemplate()->text();
        }

        return view('campaign::campaigns.stats', [
            'beamJournalConfigured' => $this->beamJournalConfigured,
            'campaign' => $campaign,
            'variants' => $variants,
            'variantBannerLinks' => $variantBannerLinks,
            'variantBannerTexts' => $variantBannerTexts,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function destroy(Campaign $campaign)
    {
        //
    }
}
