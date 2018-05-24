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
                    'title' => [
                        'orderable' => false,
                        'priority' => 1,
                    ],
                    'pageviews_all' => [
                        'header' => 'all pageviews',
                        'render' => 'numberStat',
                        'priority' => 2,
                    ],
                    'pageviews_signed_in' => [
                        'header' => 'signed in pageviews',
                        'render' => 'numberStat',
                        'priority' => 3,
                    ],
                    'pageviews_subscribers' => [
                        'header' => 'subscriber pageviews',
                        'render' => 'numberStat',
                        'priority' => 3,
                    ],
                    'avg_timespent_all' => [
                        'header' => 'avg time all',
                        'render' => 'durationStat',
                        'priority' => 2,
                    ],
                    'avg_timespent_signed_in' => [
                        'header' => 'avg time signed in',
                        'render' => 'durationStat',
                        'priority' => 3,
                    ],
                    'avg_timespent_subscribers' => [
                        'header' => 'avg time subscribers',
                        'render' => 'durationStat',
                        'priority' => 3,
                    ],
                    'conversions_count' => [
                        'header' => 'conversions',
                        'render' => 'numberStat',
                        'priority' => 2,
                    ],
                    'conversions_sum' => [
                        'header' => 'amount',
                        'render' => 'multiNumberStat',
                        'priority' => 2,
                    ],
                    'conversions_avg' => [
                        'header' => 'avg amount',
                        'render' => 'multiNumberStat',
                        'priority' => 3,
                    ],
                    'sections[, ].name' => [
                        'header' => 'sections',
                        'orderable' => false,
                        'filter' => $sections,
                        'priority' => 4,
                    ],
                    'published_at' => [
                        'header' => 'published',
                        'render' => 'date',
                        'priority' => 5,
                    ],
                ],
                'dataSource' => route('authors.dtArticles', $author),
                'order' => [7, 'desc'],
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
