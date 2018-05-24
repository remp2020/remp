@extends('layouts.app')

@section('title', 'Articles - Pageview stats')

@section('content')

    <div class="c-header">
        <h2>Articles - Pageview stats</h2>
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

    <div class="card">

        <div class="card-header">
            <h2>Pageview stats <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'title' => [
                    'orderable' => false,
                    'priority' => 1,
                ],
                'pageviews_all' => [
                    'header' => 'all pageviews',
                    'priority' => 2,
                ],
                'pageviews_signed_in' => [
                    'header' => 'signed in pageviews',
                    'priority' => 5,
                ],
                'pageviews_subscribers' => [
                    'header' => 'subscriber pageviews',
                    'priority' => 5,
                ],
                'avg_sum_all' => [
                    'header' => 'avg time all',
                    'render' => 'duration',
                    'priority' => 2,
                ],
                'avg_sum_signed_in' => [
                    'header' => 'avg time signed in',
                    'render' => 'duration',
                    'priority' => 5,
                ],
                'avg_sum_subscribers' => [
                    'header' => 'avg time subscribers',
                    'render' => 'duration',
                    'priority' => 5,
                ],
                'authors' => [
                    'header' => 'authors',
                    'orderable' => false,
                    'filter' => $authors,
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
                    'priority' => 1,
                ],
            ],
            'dataSource' => route('articles.dtPageviews'),
            'order' => [4, 'desc'],
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

@endsection
