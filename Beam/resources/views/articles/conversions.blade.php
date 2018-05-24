@extends('layouts.app')

@section('title', 'Articles - Conversion stats')

@section('content')

    <div class="c-header">
        <h2>Articles - Conversion stats</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-3">
                <h4>Filter by article publish date</h4>
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

            <div class="col-md-3">
                <h4>Filter by conversion date</h4>
                <div class="input-group m-b-10">
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container fg-line">
                        {!! Form::datetime('conversion_from', $conversionFrom, array_filter([
                            'class' => 'form-control date-picker',
                            'placeholder' => 'Conversion from...'
                        ])) !!}
                    </div>
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container fg-line">
                        <div class="dtp-container fg-line">
                            {!! Form::datetime('conversion_to', $conversionTo, array_filter([
                                'class' => 'form-control date-picker',
                                'placeholder' => 'Conversion to...'
                            ])) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Conversion stats <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'title' => [
                    'orderable' => false,
                    'priority' => 1,
                ],
                'conversions_count' => [
                    'header' => 'conversions',
                    'priority' => 2,
                ],
                'amount' => [
                    'header' => 'amount',
                    'render' => 'array',
                    'priority' => 1,
                ],
                'average' => [
                    'header' => 'average',
                    'render' => 'array',
                    'priority' => 2,
                ],
                'authors' => [
                    'header' => 'authors',
                    'orderable' => false,
                    'filter' => $authors,
                    'priority' => 2,
                ],
                'sections[, ].name' => [
                    'header' => 'sections',
                    'orderable' => false,
                    'filter' => $sections,
                    'priority' => 3,
                ],
                'published_at' => [
                    'header' => 'published',
                    'render' => 'date',
                    'priority' => 3,
                ],
            ],
            'dataSource' => route('articles.dtConversions'),
            'order' => [5, 'desc'],
            'requestParams' => [
                'published_from' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"published_from\"]", {hour:0,minute:0,second:0,millisecond:0})',
                'published_to' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"published_to\"]", {hour:23,minute:59,second:59,millisecond:999})',
                'conversion_from' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"conversion_from\"]", {hour:0,minute:0,second:0,millisecond:0})',
                'conversion_to' => '$.fn.datetimepicker.isoDateFromSelector("[name=\"conversion_to\"]", {hour:23,minute:59,second:59,millisecond:999})',
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
                [
                    'event' => 'dp.change',
                    'selector' => '[name="conversion_from"]'
                ],
                [
                    'event' => 'dp.change',
                    'selector' => '[name="conversion_to"]',
                ],
            ],
        ]) !!}

    </div>

@endsection
