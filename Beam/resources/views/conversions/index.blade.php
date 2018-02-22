@extends('layouts.app')

@section('title', 'Conversions')

@section('content')

    <div class="c-header">
        <h2>Conversions</h2>
    </div>

    <div class="well">
        <div class="row">
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
            <h2>All conversions <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'article.title' => ['header' => 'article'],
                'article.authors[, ].name' => ['header' => 'authors', 'orderable' => false, 'filter' => $authors],
                'article.sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
                'amount' => ['header' => 'amount'],
                'currency' => ['header' => 'currency', 'orderable' => false],
                'paid_at' => ['header' => 'paid at', 'render' => 'date'],
            ],
            'dataSource' => route('conversions.json'),
            'order' => [5, 'desc'],
            'requestParams' => [
                'conversion_from' => '$("[name=\"conversion_from\"]").data("DateTimePicker").date().set({hour:0,minute:0,second:0,millisecond:0}).toISOString()',
                'conversion_to' => '$("[name=\"conversion_to\"]").data("DateTimePicker").date().set({hour:23,minute:59,second:59,millisecond:999}).toISOString()',
            ],
            'refreshTriggers' => [
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
