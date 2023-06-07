@extends('beam::layouts.app')

@section('title', 'Visitors - Devices')

@section('content')

    <div class="c-header">
        <h2>Visitors - Sources</h2>
    </div>

    @include('beam::visitors/_dtFilter')

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Referer stats <small></small></h2>
                </div>

                {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'medium' => [
                            'header' => 'medium',
                            'orderable' => false,
                            'filter' => $mediums,
                            'priority' => 1,
                            'render' => 'text',
                        ],
                        'source' => [
                            'header' => 'source',
                            'orderable' => false,
                            'filter' => $sources,
                            'priority' => 1,
                            'render' => 'text',
                        ],
                        'visits_count' => [
                            'header' => 'visits count',
                            'searchable' => false,
                            'priority' => 1,
                            'render' => 'number',
                            'className' => 'text-right',
                        ],
                    ],
                    'dataSource' => route('visitors.dtReferers'),
                    'order' => [2, 'desc'],
                    'requestParams' => [
                        'visited_from' => '$(\'[name="visited_from"]\').val()',
                        'visited_to' => '$(\'[name="visited_to"]\').val()',
                        'tz' => 'Intl.DateTimeFormat().resolvedOptions().timeZone',
                        'subscriber' => '$("[name=\"subscriber\"]:checked").val()',
                    ],
                    'refreshTriggers' => [
                        [
                            'event' => 'change',
                            'selector' => '[name="visited_from"]'
                        ],
                        [
                            'event' => 'change',
                            'selector' => '[name="visited_to"]',
                        ],
                        [
                            'event' => 'change',
                            'selector' => '[name="subscriber"]',
                        ],
                    ],
                    'exportColumns' => [0,1,2],
                ]) !!}

            </div>
        </div>
    </div>


@endsection
