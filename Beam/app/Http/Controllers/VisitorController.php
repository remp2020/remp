<?php

namespace App\Http\Controllers;

use App\SessionDevice;
use App\SessionReferer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\Datatables\Datatables;

class VisitorController extends Controller
{
    public function devices(Request $request)
    {
        return response()->view('visitors.devices', [
            'visitedFrom' => $request->input('visited_from', Carbon::now()->subMonth()),
            'visitedTo' => $request->input('visited_to', Carbon::now()),
            'subscriber' => $request->input('subscriber', "1"),

            'brands' => SessionDevice::distinct()->whereNotNull('brand')->pluck('brand', 'brand'),
            'models' => SessionDevice::distinct()->whereNotNull('model')->pluck('model', 'model'),
            'osNames' => SessionDevice::distinct()->whereNotNull('os_name')->pluck('os_name', 'os_name'),
        ]);
    }

    public function sources(Request $request)
    {
        return response()->view('visitors.sources', [
            'visitedFrom' => $request->input('visited_from', Carbon::now()->subMonth()),
            'visitedTo' => $request->input('visited_to', Carbon::now()),
            'subscriber' => $request->input('subscriber', "1"),

            'mediums' => SessionReferer::distinct()->whereNotNull('medium')->pluck('medium', 'medium'),
            'sources' => SessionReferer::distinct()->whereNotNull('source')->pluck('source', 'source'),
        ]);
    }

    public function dtBrowsers(Request $request, Datatables $datatables)
    {
        $devices = SessionDevice::selectRaw(implode(',', [
            'client_name',
            'client_type',
            'SUM(count) as visits_count',
        ]))->groupBy('client_name', 'client_type');

        if ($request->input('visited_from')) {
            $devices->where('time_from', '>=', $request->input('visited_from'));
        }
        if ($request->input('visited_to')) {
            $devices->where('time_to', '<=', $request->input('visited_to'));
        }
        if ($request->input('subscriber') !== null) {
            $devices->where(['subscriber' => $request->input('subscriber')]);
        }

        return $datatables->of($devices)
            ->filterColumn('model', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('model', $values);
            })
            ->filterColumn('brand', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('brand', $values);
            })
            ->filterColumn('os_name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('os_name', $values);
            })
            ->make(true);
    }

    public function dtDevices(Request $request, Datatables $datatables)
    {
        $devices = SessionDevice::selectRaw(implode(',', [
            'model',
            'brand',
            'os_name',
            'SUM(count) as visits_count',
        ]))->whereNotNull('model')->groupBy('model', 'brand', 'os_name');

        if ($request->input('visited_from')) {
            $devices->where('time_from', '>=', $request->input('visited_from'));
        }
        if ($request->input('visited_to')) {
            $devices->where('time_to', '<=', $request->input('visited_to'));
        }
        if ($request->input('subscriber') !== null) {
            $devices->where(['subscriber' => $request->input('subscriber')]);
        }

        return $datatables->of($devices)
            ->filterColumn('model', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('model', $values);
            })
            ->filterColumn('brand', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('brand', $values);
            })
            ->filterColumn('os_name', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('os_name', $values);
            })
            ->make(true);
    }

    public function dtReferers(Request $request, Datatables $datatables)
    {
        $devices = SessionReferer::selectRaw(implode(',', [
            'medium',
            'source',
            'SUM(count) as visits_count',
        ]))->groupBy('medium', 'source');

        if ($request->input('visited_from')) {
            $devices->where('time_from', '>=', $request->input('visited_from'));
        }
        if ($request->input('visited_to')) {
            $devices->where('time_to', '<=', $request->input('visited_to'));
        }
        if ($request->input('subscriber') !== null) {
            $devices->where(['subscriber' => $request->input('subscriber')]);
        }

        return $datatables->of($devices)
            ->filterColumn('source', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('source', $values);
            })
            ->filterColumn('medium', function (Builder $query, $value) {
                $values = explode(",", $value);
                $query->whereIn('medium', $values);
            })
            ->make(true);
    }
}
