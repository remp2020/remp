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
                'title' => ['orderable' => false],
                'conversions_count' => ['header' => 'conversions'],
                'amount' => ['header' => 'amount'],
                'authors[, ].name' => ['header' => 'authors', 'orderable' => false, 'filter' => $authors],
                'sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
                'published_at' => ['header' => 'published at', 'render' => 'date'],
            ],
            'dataSource' => route('articles.dtConversions'),
            'order' => [5, 'desc'],
            'requestParams' => [
                'published_from' => '$("[name=\"published_from\"]").data("DateTimePicker").date().set({hour:0,minute:0,second:0,millisecond:0}).toISOString()',
                'published_to' => '$("[name=\"published_to\"]").data("DateTimePicker").date().set({hour:23,minute:59,second:59,millisecond:999}).toISOString()',
                'conversion_from' => '$("[name=\"conversion_from\"]").data("DateTimePicker").date().set({hour:0,minute:0,second:0,millisecond:0}).toISOString()',
                'conversion_to' => '$("[name=\"conversion_to\"]").data("DateTimePicker").date().set({hour:23,minute:59,second:59,millisecond:999}).toISOString()',
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
