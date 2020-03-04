@extends('layouts.app')

@section('title', 'Show author - ' . $author->name)

@section('content')

    <div class="c-header">
        <h2>{{ $author->name }}</h2>
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
                        'render' => 'number',
                        'priority' => 2,
                    ],
                    'pageviews_signed_in' => [
                        'header' => 'signed in pageviews',
                        'render' => 'number',
                        'priority' => 3,
                    ],
                    'pageviews_subscribers' => [
                        'header' => 'subscriber pageviews',
                        'render' => 'number',
                        'priority' => 3,
                    ],
                    'avg_timespent_all' => [
                        'header' => 'avg time all',
                        'render' => 'duration',
                        'priority' => 2,
                    ],
                    'avg_timespent_signed_in' => [
                        'header' => 'avg time signed in',
                        'render' => 'duration',
                        'priority' => 3,
                    ],
                    'avg_timespent_subscribers' => [
                        'header' => 'avg time subscribers',
                        'render' => 'duration',
                        'priority' => 3,
                    ],
                    'conversions_count' => [
                        'header' => 'conversions',
                        'render' => 'number',
                        'priority' => 2,
                    ],
                    'conversions_sum' => [
                        'header' => 'amount',
                        'render' => 'array',
                        'priority' => 2,
                    ],
                    'conversions_avg' => [
                        'header' => 'avg amount',
                        'render' => 'array',
                        'priority' => 3,
                    ],
                    'sections[, ].name' => [
                        'header' => 'sections',
                        'orderable' => false,
                        'filter' => $sections,
                        'priority' => 4,
                    ],
                    'tags[, ].name' => [
                        'header' => 'tags',
                        'orderable' => false,
                        'filter' => $tags,
                        'priority' => 5,
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
                    'published_from' => '$(\'[name="published_from"]\').val()',
                    'published_to' => '$(\'[name="published_to"]\').val()'
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
