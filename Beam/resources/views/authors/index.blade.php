@extends('layouts.app')

@section('title', 'Authors')

@section('content')

    <div class="c-header">
        <h2>Authors</h2>
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
            <h2>Author stats <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => ['header' => 'author', 'orderable' => false, 'filter' => $authors],
                'articles_count' => ['header' => 'articles'],
                'conversions_count' => ['header' => 'conversions'],
                'conversions_amount' => ['header' => 'amount', 'render' => 'array'],
                'pageviews_count' => ['header' => 'pageviews'],
                'avg_timespent' => ['header' => 'avg time read', 'render' => 'duration'],
            ],
            'dataSource' => route('authors.dtAuthors'),
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
