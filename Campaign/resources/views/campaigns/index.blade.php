@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')

    <div class="c-header">
        <h2>CAMPAIGNS</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Scheduled campaigns<small></small></h2>
            <div class="actions">
                <a href="{{ route('schedule.index') }}" class="btn palette-Cyan bg waves-effect">View all schedules</a>
            </div>
        </div>
        <div class="card-body">
            {!! Widget::run('DataTable', [
                'colSettings' => [
                    'campaign' => [
                        'header' => 'Campaign',
                        'priority' => 1,
                    ],
                    'status' => [
                        'header' => 'Status',
                        'priority' => 1,
                        'render' => 'badge',
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
                ],
                'dataSource' => route('schedule.json', ['active' => true, 'limit' => 5]),
                'rowActions' => [
                    ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit schedule'],
                    ['name' => 'start', 'class' => 'zmdi-palette-Cyan zmdi-play', 'title' => 'Start schedule'],
                    ['name' => 'pause', 'class' => 'zmdi-palette-Cyan zmdi-pause', 'title' => 'Pause schedule'],
                    ['name' => 'stop', 'class' => 'zmdi-palette-Cyan zmdi-stop', 'title' => 'Stop schedule'],
                    ['name' => 'destroy', 'class' => 'zmdi-palette-Cyan zmdi-delete', 'title' => 'Delete schedule'],
                ],
                'displaySearchAndPaging' => false,
                'refreshTriggers' => [
                    [
                    // refresh when campaign's active toggle is toggled
                    'event' => 'campaign_active_toggled',
                    'selector' => 'document'
                    ],
                ],
                'order' => [2, 'desc'],
            ]) !!}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Campaign list <small></small></h2>
                    <div class="actions">
                        <a href="{{ route('comparison.index') }}" class="btn palette-Cyan bg waves-effect">Compare campaigns</a>
                        <a href="{{ route('campaigns.create') }}" class="btn palette-Cyan bg waves-effect">Add new campaign</a>
                    </div>
                </div>

                <div class="card-body">
                    {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'name' => [
                            'priority' => 1,
                        ],
                        'variants' => [
                            'header' => 'Variants',
                            'orderable' => false,
                            'priority' => 3,
                            'render' => 'array',
                        ],
                        'segments' => [
                            'header' => 'Segments',
                            'priority' => 10,
                            'render' => 'array',
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
                            'header' => 'Signed-in state',
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
                        'is_running' => true
                    ],
                    'dataSource' => route('campaigns.json'),
                    'rowActions' => [
                        ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit campaign'],
                        ['name' => 'copy', 'class' => 'zmdi-palette-Cyan zmdi-copy', 'title' => 'Copy campaign'],
                        ['name' => 'stats', 'class' => 'zmdi-palette-Cyan zmdi-chart', 'title' => 'Campaign stats'],
                        ['name' => 'compare', 'onclick' => 'addCampaignToComparison(event, this)', 'class' => 'zmdi-palette-Cyan zmdi-swap ', 'title' => 'Add campaign to comparison']
                    ],
                    'order' => [8, 'desc'],
                ]) !!}
                </div>
            </div>
        </div>
    </div>
    <script>
        function addCampaignToComparison(e, anchor) {
            e.preventDefault();
            $.ajax({
                url: anchor.href,
                type: 'PUT'
            }).done(function(data) {
                $.notify({
                    message: 'Campaign was added to comparison </br>' +
                    '<a class="notifyLink" href="{!! route('comparison.index') !!}">Go to comparison page.</a>'
                }, {
                    allow_dismiss: false,
                    type: 'info'
                });
            }).fail(function() {
                var errorMsg = 'Unable to add campaign to comparison';
                console.warn(errorMsg);
                $.notify({
                    message: errorMsg
                }, {
                    allow_dismiss: false,
                    type: 'danger'
                });
            });
        }
    </script>

@endsection
