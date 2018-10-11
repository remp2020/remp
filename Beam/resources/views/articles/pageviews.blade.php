@extends('layouts.app')

@section('title', 'Articles - Pageview stats')

@section('content')

    <div class="c-header">
        <h2>Articles - Pageview stats</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by publish date</h4>
                <div id="smart-range-selector">
                    {!! Form::hidden('published_from', $publishedFrom) !!}
                    {!! Form::hidden('published_to', $publishedTo) !!}
                    <smart-range-selector from="{{$publishedFrom}}" to="{{$publishedTo}}" :callback="callback">
                    </smart-range-selector>
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
                    'priority' => 5
                ],
                'pageviews_subscribers_ratio' => [
                    'header' => 'pageviews subscribers %',
                    'render' => 'percentage',
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
                'published_from' => '$(\'[name="published_from"]\').val()',
                'published_to' => '$(\'[name="published_to"]\').val()',
                'tz' => 'Intl.DateTimeFormat().resolvedOptions().timeZone'
            ],
            'refreshTriggers' => [
                [
                    'event' => 'change',
                    'selector' => '[name="published_from"]'
                ],
                [
                    'event' => 'change',
                    'selector' => '[name="published_to"]',
                ],
            ],
        ]) !!}

    </div>

    <script type="text/javascript">
        new Vue({
            el: "#smart-range-selector",
            components: {
                SmartRangeSelector
            },
            methods: {
                callback: function (from, to) {
                    $('[name="published_from"]').val(from);
                    $('[name="published_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
