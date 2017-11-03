<?php

namespace App\Http\Controllers;

use App\Campaign;
use App\Http\Requests\ScheduleRequest;
use App\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ScheduleController extends Controller
{
    public function index()
    {
        return view('schedule.index');
    }

    public function json(Datatables $dataTables)
    {
        $schedule = Schedule::query()
            ->with('campaign')
            ->orderBy('start_time', 'DESC')
            ->orderBy('end_time', 'DESC');

        return $dataTables->of($schedule)
            ->addColumn('actions', function (Schedule $schedule) {
                return [
                    'edit' => !$schedule->isStopped() ? route('schedule.edit', $schedule) : null,
                    'start' => $schedule->isRunnable() ? route('schedule.start', $schedule) : null,
                    'pause' => $schedule->isRunning() ? route('schedule.pause', $schedule) : null,
                    'stop' => $schedule->isRunning() ? route('schedule.stop', $schedule) : null,
                ];
            })
            ->addColumn('status', function(Schedule $schedule) {
                if ($schedule->isRunning()) {
                    return 'Running';
                }
                if ($schedule->status === Schedule::STATUS_PAUSED) {
                    return 'Paused';
                }
                if ($schedule->status === Schedule::STATUS_STOPPED) {
                    return 'Stopped';
                }
                if ($schedule->start_time > Carbon::now()) {
                    return 'Waiting for start';
                }
                if (!$schedule->isRunnable()) {
                    return 'Finished';
                }
                throw new \Exception('unhandled schedule status');
            })
            ->editColumn('start_time', function (Schedule $schedule) {
                return $schedule->start_time;
            })
            ->editColumn('end_time', function (Schedule $schedule) {
                return $schedule->end_time;
            })
            ->rawColumns(['actions', 'status'])
            ->setRowId('id')
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $schedule = new Schedule();
        $schedule->fill(old());
        $campaigns = Campaign::whereActive(true)->get();

        return view('schedule.create', [
            'schedule' => $schedule,
            'campaigns' => $campaigns,
        ]);
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

        return redirect(route('schedule.index'))->with('success', sprintf(
            "Campaign %s scheduled from %s to %s",
            $schedule->campaign,
            Carbon::parse($schedule->start_time)->toDayDateTimeString(),
            Carbon::parse($schedule->end_time)->toDayDateTimeString()
        ));
    }

    public function edit(Schedule $schedule)
    {
        $schedule->fill(old());
        $campaigns = Campaign::whereActive(true)->get();

        return view('schedule.edit', [
            'schedule' => $schedule,
            'campaigns' => $campaigns,
        ]);
    }

    public function update(ScheduleRequest $request, Schedule $schedule)
    {
        $schedule->fill($request->all());
        $schedule->save();

        return redirect(route('schedule.index'))->with('success', sprintf(
            "Campaign %s rescheduled from %s to %s",
            $schedule->campaign->name,
            Carbon::parse($schedule->start_time)->toDayDateTimeString(),
            Carbon::parse($schedule->end_time)->toDayDateTimeString()
        ));
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function pause(Schedule $schedule)
    {
        $schedule->status = Schedule::STATUS_PAUSED;
        $schedule->save();
        return redirect(route('schedule.index'))->with('success', sprintf(
            "Schedule for campaign %s is now paused",
            $schedule->campaign->name
        ));
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function start(Schedule $schedule)
    {
        $schedule->status = Schedule::STATUS_RUNNING;
        $schedule->save();
        return redirect(route('schedule.index'))->with('success', sprintf(
            "Schedule for campaign %s was started manually",
            $schedule->campaign->name
        ));
    }

    /**
     * @param Schedule $schedule
     * @return \Illuminate\Http\Response
     */
    public function stop(Schedule $schedule)
    {
        $schedule->status = Schedule::STATUS_STOPPED;
        $schedule->save();
        return redirect(route('schedule.index'))->with('success', sprintf(
            "Schedule for campaign %s was stopped",
            $schedule->campaign->name
        ));
    }
}
