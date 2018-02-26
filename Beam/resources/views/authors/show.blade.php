@extends('layouts.app')

@section('title', 'Show author - ' . $author->name)

@section('content')

    <div class="c-header">
        <h2>{{ $author->name }}</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-3">
                <h4>Filter by publish date</h4>
                <div class="input-group m-b-10">
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container fg-line">
                        {!! Form::datetime('published_from', $publishedFrom, array_filter([
                            'class' => 'form-control date-picker',
                            'placeholder' => 'Published from...'
                        ])) !!}
                    </div>
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container fg-line">
                        <div class="dtp-container fg-line">
                            {!! Form::datetime('published_to', $publishedTo, array_filter([
                                'class' => 'form-control date-picker',
                                'placeholder' => 'Published to...'
                            ])) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show author <small>{{ $author->name }}</small></h2>
            </div>

            {!! Widget::run('DataTable', [
                'colSettings' => [
                    'title' => ['orderable' => false],
                    'pageview_sum' => ['header' => 'pageviews', 'render' => 'numberStat'],
                    'timespent_sum' => ['header' => 'total time read', 'render' => 'durationStat'],
                    'avg_sum' => ['header' => 'avg time read', 'render' => 'durationStat'],
                    'conversions_count' => ['header' => 'conversions', 'render' => 'numberStat'],
                    'conversions_sum' => ['header' => 'amount', 'render' => 'multiNumberStat'],
                    'conversions_avg' => ['header' => 'avg amount', 'render' => 'multiNumberStat'],
                    'sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
                    'published_at' => ['header' => 'published', 'render' => 'date'],
                ],
                'dataSource' => route('authors.dtArticles', $author),
                'order' => [5, 'desc'],
                'requestParams' => [
                    'published_from' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"published_from\"]", {hour:0,minute:0,second:0,millisecond:0})',
                    'published_to' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"published_to\"]", {hour:23,minute:59,second:59,millisecond:999})',
                ],
                'refreshTriggers' => [
                    [
                        'event' => 'dp.change',
                        'selector' => '[name="published_from"]'
                    ],
                    [
                        'event' => 'dp.change',
                        'selector' => '[name="published_to"]',
                    ],
                ],
            ]) !!}

        </div>
    </div>

@endsection
