@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Scheduled campaigns<small></small></h2>
            <div class="actions">
                <a href="{{ route('schedule.index') }}" class="btn palette-Cyan bg waves-effect">View all schedules</a>
                <a href="{{ route('schedule.create') }}" class="btn palette-Cyan bg waves-effect">Schedule new run</a>
            </div>
        </div>
        <div class="card-body">
            {!! Widget::run('DataTable', [
            'colSettings' => [
                'campaign' => [
                    'header' => 'Campaign',
                ],
                'start_time' => [
                    'header' => 'Scheduled start date',
                    'render' => 'date',
                ],
                'end_time' => [
                    'header' => 'Scheduled end date',
                    'render' => 'date',
                ],
                'status' => [
                    'header' => 'Status',
                ],
            ],
            'dataSource' => route('schedule.json', ['active' => true, 'limit' => 5]),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                ['name' => 'start', 'class' => 'zmdi-palette-Cyan zmdi-play'],
                ['name' => 'pause', 'class' => 'zmdi-palette-Cyan zmdi-pause'],
                ['name' => 'stop', 'class' => 'zmdi-palette-Cyan zmdi-stop'],
                ['name' => 'destroy', 'class' => 'zmdi-palette-Cyan zmdi-delete'],
            ],
            'displaySearchAndPaging' => false,
            'refreshTriggers' => [
                [
                // refresh when campaign's active toggle is toggled
                'event' => 'campaign_active_toggled',
                'selector' => 'document'
                ],
            ],
            ]) !!}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Campaign list <small></small></h2>
                    <div class="actions">
                        <a href="{{ route('campaigns.create') }}" class="btn palette-Cyan bg waves-effect">Add new campaign</a>
                    </div>
                </div>

                <div class="card-body">
                    {!! Widget::run('DataTable', [
                    'responsive' => true,
                    'colSettings' => [
                        'name',
                        'banner' => [
                            'header' => 'Banner'
                        ],
                        'alt_banner' => [
                            'header' => 'Banner B'
                        ],
                        'segments' => [
                            'header' => 'Segments'
                        ],
                        'countries' => [
                            'header' => 'Countries'
                        ],
                        'active' => [
                            'header' => 'Is active'
                        ],
                        'signed_in' => [
                            'header' => 'Signed in',
                            'render' => 'boolean',
                        ],
                        'devices' => [
                            'header' => 'Devices'
                        ],
                        'created_at' => [
                            'header' => 'Created at',
                            'render' => 'date',
                        ],
                        'updated_at' => [
                            'header' => 'Updated at',
                            'render' => 'date',
                        ],
                    ],
                    'columnDefs' => [
                        // campaign name
                        [
                            'responsivePriority' => 1,
                            'targets' => 0,
                        ],
                        // banner
                        [
                            'responsivePriority' => 9,
                            'targets' => 1,
                        ],
                        // banner b
                        [
                            'responsivePriority' => 9,
                            'targets' => 2,
                        ],
                        // segments
                        [
                            'responsivePriority' => 10,
                            'targets' => 3,
                        ],
                        // countries
                        [
                            'responsivePriority' => 10,
                            'targets' => 4,
                        ],
                        // is active
                        [
                            'responsivePriority' => 5,
                            'targets' => 5,
                        ],
                        // signed in
                        [
                            'responsivePriority' => 10,
                            'targets' => 6,
                        ],
                        // devices
                        [
                            'responsivePriority' => 10,
                            'targets' => 7,
                        ],
                        // created at
                        [
                            'responsivePriority' => 9,
                            'targets' => -3,
                        ],
                        // updated at
                        [
                            'responsivePriority' => 1,
                            'targets' => -2,
                        ],
                        // actions
                        [
                            'responsivePriority' => 1,
                            'targets' => -1,
                        ],
                    ],
                    'rowHighlights' => [
                        'active' => true
                    ],
                    'dataSource' => route('campaigns.json'),
                    'rowActions' => [
                        ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                        ['name' => 'copy', 'class' => 'zmdi-palette-Cyan zmdi-copy'],
                    ],
                    'order' => [9, 'desc'],
                ]) !!}
                </div>
            </div>
        </div>
    </div>

@endsection
