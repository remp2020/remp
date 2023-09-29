<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\CampaignBanner;
use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Remp\LaravelHelpers\Resources\JsonResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Yajra\Datatables\Datatables;

class ScheduleController extends Controller
{
    public function index()
    {
        $variants = CampaignBanner::with('banner')
            ->whereNotNull('banner_id')
            ->get()
            ->pluck('banner.name', 'banner.id');

        return response()->format([
            'html' => view('schedule.index', [
                'variants' => $variants
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
    public function json(Request $request, Datatables $dataTables, Campaign $campaign = null)
    {
        $scheduleSelect = Schedule::select('schedules.*', 'campaigns.public_id as campaign_public_id')
            ->join('campaigns', 'schedules.campaign_id', '=', 'campaigns.id');

        if (!is_null($campaign)) {
            $scheduleSelect->where('campaign_id', '=', $campaign->id);
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

        return $dataTables->of($scheduleSelect)
            ->addColumn('actions', function (Schedule $s) {
                return [
                    'edit' => !$s->isStopped() ? route('schedule.edit', $s) : null,
                    'start' => $s->isRunnable() ? route('schedule.start', $s) : null,
                    'pause' => $s->isRunning() ? route('schedule.pause', $s) : null,
                    'stop' => $s->isRunning() || $s->isPaused() ? route('schedule.stop', $s) : null,
                    'destroy' => $s->isEditable() ? route('schedule.destroy', $s) : null,
                ];
            })
            ->addColumn('campaign', function (Schedule $schedule) {
                return [
                    'url' => route('campaigns.edit', ['campaign' => $schedule->campaign]),
                    'text' => $schedule->campaign->name,
                ];
            })
            ->filterColumn('campaign', function (Builder $query, $value) {
                $query->whereHas('campaign', function (Builder $query) use ($value) {
                    $query->where('campaigns.name', 'like', "%{$value}%");
                });
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
                    $link = link_to(
                        route('banners.edit', $variant['banner_id']),
                        $variant->banner->name
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
            ->addColumn('action_methods', [
                'start' => 'POST',
                'pause' => 'POST',
                'stop' => 'POST',
                'destroy' => 'DELETE',
            ])
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

    /**
     * Store a newly created resource in storage.
     *
     * @param ScheduleRequest|Request $request
     * @return \Illuminate\Http\Response
     */
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

    public function edit(Schedule $schedule)
    {
        $schedule->fill(old());

        return view('schedule.edit', [
            'schedule' => $schedule,
        ]);
    }

    public function update(ScheduleRequest $request, Schedule $schedule)
    {
        $schedule->fill($request->all());
        $schedule->save();

        return response()->format([
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
                "Campaign %s rescheduled starting on %s and ending on %s",
                $schedule->campaign->name,
                Carbon::parse($schedule->start_time)->toDayDateTimeString(),
                Carbon::parse($schedule->end_time)->toDayDateTimeString()
            )),
            'json' => new ScheduleResource($schedule),
        ]);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return response()->format([
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
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
                'html' => redirect(route('campaigns.index'))->with('success', sprintf(
                    "Schedule for campaign %s was not running, pause request ignored",
                    $schedule->campaign->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot pause schedule: not running')),
            ]);
        }

        $schedule->status = Schedule::STATUS_PAUSED;
        $schedule->save();

        return response()->format([
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
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
                'html' => redirect(route('campaigns.index'))->with('success', sprintf(
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
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
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
                'html' => redirect(route('campaigns.index'))->with('success', sprintf(
                    "Schedule for campaign %s was not running, stop request ignored",
                    $schedule->campaign->name
                )),
                'json' => new JsonResource(new BadRequestHttpException('cannot stop schedule: not running')),
            ]);
        }

        $schedule->status = Schedule::STATUS_STOPPED;
        $schedule->save();

        return response()->format([
            'html' => redirect(route('campaigns.index'))->with('success', sprintf(
                "Schedule for campaign %s was stopped",
                $schedule->campaign->name
            )),
            'json' => new ScheduleResource([]),
        ]);
    }
}
