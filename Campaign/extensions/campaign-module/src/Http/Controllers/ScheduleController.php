<?php

namespace Remp\CampaignModule\Http\Controllers;

use Remp\CampaignModule\Campaign;
use Remp\CampaignModule\CampaignBanner;
use Remp\CampaignModule\CampaignCollection;
use Remp\CampaignModule\Http\Requests\ScheduleRequest;
use Remp\CampaignModule\Http\Resources\ScheduleResource;
use Remp\CampaignModule\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Remp\LaravelHelpers\Resources\JsonResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\QueryDataTable;

class ScheduleController extends Controller
{
    public function index(CampaignCollection $collection = null)
    {
        $variants = CampaignBanner::with('banner')
            ->whereNotNull('banner_id')
            ->get()
            ->pluck('banner.name', 'banner.id');

        return response()->format([
            'html' => view('campaign::schedule.index', [
                'variants' => $variants,
                'collection' => $collection,
            ]),
            'json' => ScheduleResource::collection(Schedule::paginate()),
        ]);
    }

    /**
     * Return data for Schedule Datatable.
     *
     * If `$campaign` is provided, only schedules for that one Campaign are returned.
     *
     * Request parameters can be used:
     *
     *   * `active` - (bool) display only active (running or planned) schedules.
     *   * `limit`  - (int) count of results will be limited to that number.
     *
     * @param Datatables $dataTables
     * @param Campaign|null $campaign
     * @return mixed
     * @throws \Exception
     */
    public function json(Request $request, Datatables $dataTables, Campaign $campaign = null, CampaignCollection $collection = null)
    {
        $scheduleSelect = Schedule::select('schedules.*')
            ->join('campaigns', 'schedules.campaign_id', '=', 'campaigns.id')
            ->leftJoin('campaign_collections', 'campaigns.id', '=', 'campaign_collections.campaign_id')
            ->groupBy('schedules.id');

        if (!is_null($campaign)) {
            $scheduleSelect->where('schedules.campaign_id', '=', $campaign->id);
        }

        if ($collection) {
            $scheduleSelect->where('campaign_collections.collection_id', '=', $collection->id);
        }

        if ($request->active) {
            $scheduleSelect->where(function (\Illuminate\Database\Eloquent\Builder $query) {
                $query
                    ->whereNull('end_time')
                    ->orWhere('end_time', '>=', Carbon::now());
            })
                ->whereIn('status', [Schedule::STATUS_READY, Schedule::STATUS_EXECUTED, Schedule::STATUS_PAUSED]);
        }

        if (is_numeric($request->limit)) {
            $scheduleSelect->limit($request->limit);
        }

        /** @var QueryDataTable $datatable */
        $datatable = $dataTables->of($scheduleSelect);
        return $datatable
            ->addColumn('actions', function (Schedule $s) use ($collection) {
                return [
                    'edit' => !$s->isStopped() ? route('schedule.edit', ['schedule' => $s, 'collection' => $collection]) : null,
                    'start' => $s->isRunnable() ? route('schedule.start', $s) : null,
                    'pause' => $s->isRunning() ? route('schedule.pause', $s) : null,
                    'stop' => $s->isRunning() || $s->isPaused() ? route('schedule.stop', $s) : null,
                    'destroy' => $s->isEditable() ? route('schedule.destroy', $s) : null,
                ];
            })
            ->addColumn('campaign', function (Schedule $schedule) use ($collection) {
                return [
                    'url' => route('campaigns.edit', ['campaign' => $schedule->campaign, 'collection' => $collection]),
                    'text' => $schedule->campaign->name,
                ];
            })
            ->filterColumn('campaign', function (Builder $query, $value) {
                $query->whereHas('campaign', function (Builder $query) use ($value) {
                    $query->where('campaigns.name', 'like', "%{$value}%");
                });
            })
            ->addColumn('campaign_public_id', function ($schedule) {
                return $schedule->campaign->public_id;
            })
            ->filterColumn('campaign_public_id', function (Builder $query, $value) {
                $query->whereHas('campaign', function (Builder $query) use ($value) {
                    $query->where('campaigns.public_id', $value);
                });
            })
            ->orderColumn('campaign', function (Builder $query, $order) {
                $query->orderBy('campaigns.name', $order);
            })
            ->addColumn('variants', function (Schedule $schedule) {
                $data = $schedule->campaign->campaignBanners->all();
                $variants = [];

                foreach ($data as $variant) {
                    $proportion = $variant['proportion'];
                    if ($proportion === 0) {
                        continue;
                    }

                    if ($variant['control_group'] === 1) {
                        // handle control group
                        $variants[] = "Control Group&nbsp;({$proportion}%)";
                        continue;
                    }

                    // handle variants with banner
                    $link = html()->a(
                        href: route('banners.edit', $variant['banner_id']),
                        contents: $variant->banner->name
                    );

                    $variants[] = "{$link}&nbsp;({$proportion}%)";
                }
                return $variants;
            })
            ->filterColumn('variants', function (Builder $query, $value) {
                $values = explode(',', $value);
                $filterQuery = \DB::table('schedules')
                    ->select(['schedules.id'])
                    ->join('campaign_banners', 'campaign_banners.campaign_id', '=', 'schedules.campaign_id')
                    ->whereIn('campaign_banners.banner_id', $values)
                    ->where('campaign_banners.proportion', '>', 0);
                $query->whereIn('schedules.id', $filterQuery);
            })
            ->addColumn('action_methods', function (Schedule $schedule) {
                return [
                    'start' => 'POST',
                    'pause' => 'POST',
                    'stop' => 'POST',
                    'destroy' => 'DELETE',
                ];
            })
            ->addColumn('status', function (Schedule $schedule) {
                if ($schedule->isRunning()) {
                    return [['class' => 'badge-success', 'text' => 'Running']];
                }
                if ($schedule->status === Schedule::STATUS_PAUSED) {
                    return [['class' => 'badge-primary', 'text' => 'Paused']];
                }
                if ($schedule->status === Schedule::STATUS_STOPPED) {
                    return [['class' => 'badge-default', 'text' => 'Stopped']];
                }
                if ($schedule->start_time > Carbon::now()) {
                    return [['class' => 'badge-primary', 'text' => 'Waiting for start']];
                }
                if (!$schedule->isRunnable()) {
                    return [['class' => 'badge-default', 'text' => 'Finished']];
                }
                throw new \Exception('unhandled schedule status');
            })
            ->orderColumn('created_at', function (Builder $query, $order) {
                $query->orderBy('schedules.created_at', $order);
            })
            ->orderColumn('updated_at', function (Builder $query, $order) {
                $query->orderBy('schedules.updated_at', $order);
            })
            ->orderColumn('status', function (Builder $query, $order) {
                $query->orderBy('status', $order);
            })
            ->rawColumns(['campaign.text', 'actions', 'action_methods', 'status', 'campaign'])
            ->setRowId('id')
            ->make(true);
    }

    public function store(ScheduleRequest $request)
    {
        $schedule = new Schedule();
        $schedule->fill($request->all());
        $schedule->save();

        return response()->format([
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
                "Campaign %s scheduled from %s to %s",
                $schedule->campaign->name,
                Carbon::parse($schedule->start_time)->toDayDateTimeString(),
                Carbon::parse($schedule->end_time)->toDayDateTimeString()
            )),
            'json' => new ScheduleResource($schedule),
        ]);
    }

    public function edit(Schedule $schedule, CampaignCollection $collection = null)
    {
        $schedule->fill(old());

        return view('campaign::schedule.edit', [
            'schedule' => $schedule,
            'collection' => $collection,
        ]);
    }

    public function update(ScheduleRequest $request, Schedule $schedule, CampaignCollection $collection = null)
    {
        $schedule->fill($request->all());
        $schedule->save();

        return response()->format([
            'html' => redirect(route('campaigns.index', ['collection' => $collection]))->with('success', sprintf(
                "Campaign %s rescheduled starting on %s and ending on %s",
                $schedule->campaign->name,
                Carbon::parse($schedule->start_time)->toDayDateTimeString(),
                Carbon::parse($schedule->end_time)->toDayDateTimeString()
            )),
            'json' => new ScheduleResource($schedule),
        ]);
    }

    public function destroy(Schedule $schedule, CampaignCollection $collection = null)
    {
        $schedule->delete();

        return response()->format([
            'html' => redirect(route('campaigns.index', ['collection' => $collection]))->with('success', sprintf(
                "Schedule for campaign %s from %s to %s was removed",
                $schedule->campaign->name,
                Carbon::parse($schedule->start_time)->toDayDateTimeString(),
                Carbon::parse($schedule->end_time)->toDayDateTimeString()
            )),
            'json' => new ScheduleResource([]),
        ]);
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function pause(Schedule $schedule)
    {
        if (!$schedule->isRunning()) {
            return response()->format([
                'html' => redirect(url()->previous())->with('success', sprintf(
                    "Schedule for campaign %s was not running, pause request ignored",
                    $schedule->campaign->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot pause schedule: not running')),
            ]);
        }

        $schedule->status = Schedule::STATUS_PAUSED;
        $schedule->save();

        return response()->format([
            'html' => redirect(url()->previous())->with('success', sprintf(
                "Schedule for campaign %s is now paused",
                $schedule->campaign->name
            )),
            'json' => new ScheduleResource([]),
        ]);
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function start(Schedule $schedule)
    {
        if (!$schedule->isRunnable()) {
            return response()->format([
                'html' => redirect(url()->previous())->with('success', sprintf(
                    "Schedule for campaign %s was not runnable, start request ignored",
                    $schedule->campaign->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot start schedule: not runnable')),
            ]);
        }

        $schedule->status = Schedule::STATUS_EXECUTED;
        if ($schedule->start_time > Carbon::now()) {
            $schedule->start_time = Carbon::now();
        }
        $schedule->save();

        return response()->format([
            'html' => redirect(url()->previous())->with('success', sprintf(
                "Schedule for campaign %s was started manually",
                $schedule->campaign->name
            )),
            'json' => new ScheduleResource([]),
        ]);
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function stop(Schedule $schedule)
    {
        if (!$schedule->isRunning() && !$schedule->isPaused()) {
            return response()->format([
                'html' => redirect(url()->previous())->with('success', sprintf(
                    "Schedule for campaign %s was not running, stop request ignored",
                    $schedule->campaign->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot stop schedule: not running')),
            ]);
        }

        $schedule->status = Schedule::STATUS_STOPPED;
        $schedule->save();

        return response()->format([
            'html' => redirect(url()->previous())->with('success', sprintf(
                "Schedule for campaign %s was stopped",
                $schedule->campaign->name
            )),
            'json' => new ScheduleResource([]),
        ]);
    }
}
