@extends('layouts.app')

@section('title', 'Visitors - Devices')

@section('content')

    <div class="c-header">
        <h2>Visitors - Sources</h2>
    </div>

    @include('visitors/_dtFilter')

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Referer stats <small></small></h2>
                </div>

                {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'medium' => ['header' => 'medium', 'orderable' => false, 'filter' => $mediums],
                        'source' => ['header' => 'source', 'orderable' => false, 'filter' => $sources],
                        'visits_count' => ['header' => 'visits count', 'searchable' => false],
                    ],
                    'dataSource' => route('visitors.dtReferers'),
                    'order' => [2, 'desc'],
                    'requestParams' => [
                        'visited_from' => '$(\'[name="visited_from"]\').val()',
                        'visited_to' => '$(\'[name="visited_to"]\').val()',
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
