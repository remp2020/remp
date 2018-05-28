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
                    'priority' => 1,
                ],
                'start_time' => [
                    'header' => 'Scheduled start date',
                    'render' => 'date',
                    'priority' => 2,
                ],
                'end_time' => [
                    'header' => 'Scheduled end date',
                    'render' => 'date',
                    'priority' => 2,
                ],
                'status' => [
                    'header' => 'Status',
                    'priority' => 1,
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
                    'colSettings' => [
                        'name' => [
                            'priority' => 1,
                        ],
                        'banner' => [
                            'header' => 'Banner',
                            'priority' => 9,
                        ],
                        'alt_banner' => [
                            'header' => 'Banner B',
                            'priority' => 9,
                        ],
                        'segments' => [
                            'header' => 'Segments',
                            'priority' => 10,
                        ],
                        'countries' => [
                            'header' => 'Countries',
                            'priority' => 10,
                        ],
                        'active' => [
                            'header' => 'Is active',
                            'priority' => 5,
                        ],
                        'signed_in' => [
                            'header' => 'Signed in',
                            'render' => 'boolean',
                            'priority' => 10,
                        ],
                        'devices' => [
                            'header' => 'Devices',
                            'priority' => 10,
                        ],
                        'created_at' => [
                            'header' => 'Created at',
                            'render' => 'date',
                            'priority' => 9,
                        ],
                        'updated_at' => [
                            'header' => 'Updated at',
                            'render' => 'date',
                            'priority' => 1,
                        ]
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
