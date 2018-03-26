@extends('layouts.app')

@section('title', 'Visitors - Devices')

@section('content')

    <div class="c-header">
        <h2>Visitors - Devices</h2>
    </div>

    @include('visitors/_dtFilter')

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Browser stats <small></small></h2>
                </div>

                {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'client_name' => ['header' => 'client name', 'orderable' => false],
                        'client_type' => ['header' => 'client type', 'orderable' => false],
                        'visits_count' => ['header' => 'visits count', 'searchable' => false],
                    ],
                    'dataSource' => route('visitors.dtBrowsers'),
                    'order' => [2, 'desc'],
                    'requestParams' => [
                        'visited_from' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"visited_from\"]", {hour:0,minute:0,second:0,millisecond:0})',
                        'visited_to' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"visited_to\"]", {hour:23,minute:59,second:59,millisecond:999})',
                        'subscriber' => '$("[name=\"subscriber\"]:checked").val()',
                    ],
                    'refreshTriggers' => [
                        [
                            'event' => 'dp.change',
                            'selector' => '[name="visited_from"]'
                        ],
                        [
                            'event' => 'dp.change',
                            'selector' => '[name="visited_to"]',
                        ],
                        [
                            'event' => 'change',
                            'selector' => '[name="subscriber"]',
                        ],
                    ],
                ]) !!}

            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Device stats <small></small></h2>
                </div>

                {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'brand' => ['header' => 'brand', 'orderable' => false, 'filter' => $brands],
                        'model' => ['header' => 'model', 'orderable' => false, 'filter' => $models],
                        'os_name' => ['header' => 'OS', 'orderable' => false, 'filter' => $osNames],
                        'visits_count' => ['header' => 'visits count', 'searchable' => false],
                    ],
                    'dataSource' => route('visitors.dtDevices'),
                    'order' => [3, 'desc'],
                    'requestParams' => [
                        'visited_from' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"visited_from\"]", {hour:0,minute:0,second:0,millisecond:0})',
                        'visited_to' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"visited_to\"]", {hour:23,minute:59,second:59,millisecond:999})',
                        'subscriber' => '$("[name=\"subscriber\"]:checked").val()',
                    ],
                    'refreshTriggers' => [
                        [
                            'event' => 'dp.change',
                            'selector' => '[name="visited_from"]'
                        ],
                        [
                            'event' => 'dp.change',
                            'selector' => '[name="visited_to"]',
                        ],
                        [
                            'event' => 'change',
                            'selector' => '[name="subscriber"]',
                        ],
                    ],
                ]) !!}

            </div>
        </div>
    </div>


@endsection
