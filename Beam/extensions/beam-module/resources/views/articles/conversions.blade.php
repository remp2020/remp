@extends('beam::layouts.app')

@section('title', 'Articles - Conversion stats')

@section('content')

    <div class="c-header">
        <h2>Articles - Conversion stats</h2>
    </div>

    <div class="well">
        <div id="smart-range-selectors" class="row">
            <div class="col-md-4">
                <h4>Filter by article publish date</h4>
                {{ html()->hidden('published_from', $publishedFrom) }}
                {{ html()->hidden('published_to', $publishedTo) }}
                <smart-range-selector from="{{$publishedFrom}}" to="{{$publishedTo}}" :callback="callbackPublished">
                </smart-range-selector>
            </div>

            <div class="col-md-4">
                <h4>Filter by conversion date</h4>
                {{ html()->hidden('conversion_from', $conversionFrom) }}
                {{ html()->hidden('conversion_to', $conversionTo) }}
                <smart-range-selector from="{{$conversionFrom}}" to="{{$conversionTo}}" :callback="callbackConversion">
                </smart-range-selector>
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
                    'render' => 'link',
                ],
                'conversions_count' => [
                    'header' => 'conversions',
                    'searchable' => false,
                    'orderSequence' => ['desc', 'asc'],
                    'priority' => 2,
                    'className' => 'text-right',
                ],
                'conversions_rate' => [
                    'searchable' => false,
                    'header' => 'conversions rate',
                    'orderSequence' => ['desc', 'asc'],
                    'priority' => 2,
                    'className' => 'text-right',
                    'tooltip' => 'The rate between conversions count and all article pageviews.'
                ],
                'amount' => [
                    'header' => 'amount',
                    'orderSequence' => ['desc', 'asc'],
                    'render' => 'array',
                    'priority' => 1,
                    'searchable' => false,
                    'className' => 'text-right',
                ],
                'average' => [
                    'header' => 'average',
                    'render' => 'array',
                    'orderSequence' => ['desc', 'asc'],
                    'priority' => 2,
                    'searchable' => false,
                    'className' => 'text-right',
                ],
                'content_type' => [
                    'header' => 'Type',
                    'orderable' => false,
                    'filter' => $contentTypes,
                    'priority' => 2,
                    'visible' => count($contentTypes) > 1,
                ],
                'authors' => [
                    'header' => 'authors',
                    'orderable' => false,
                    'filter' => $authors,
                    'priority' => 2,
                    'render' => 'raw',
                ],
                'sections[, ].name' => [
                    'header' => 'sections',
                    'orderable' => false,
                    'filter' => $sections,
                    'priority' => 3,
                ],
                'tags[, ].name' => [
                    'header' => 'tags',
                    'orderable' => false,
                    'filter' => $tags,
                    'priority' => 4,
                ],
                'published_at' => [
                    'searchable' => false,
                    'header' => 'published',
                    'render' => 'date',
                    'priority' => 3,
                ],
            ],
            'dataSource' => route('articles.dtConversions'),
            'order' => [1, 'desc'],
            'requestParams' => [
                'published_from' => '$(\'[name="published_from"]\').val()',
                'published_to' => '$(\'[name="published_to"]\').val()',
                'conversion_from' => '$(\'[name="conversion_from"]\').val()',
                'conversion_to' => '$(\'[name="conversion_to"]\').val()',
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
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_from"]'
                ],
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_to"]',
                ],
            ],
            'exportColumns' => [0,1,2,3,4,5,6,7],
        ]) !!}

    </div>

    <script type="text/javascript">
        new Vue({
            el: "#smart-range-selectors",
            components: {
                SmartRangeSelector
            },
            methods: {
                callbackPublished: function (from, to) {
                    $('[name="published_from"]').val(from);
                    $('[name="published_to"]').val(to).trigger("change");
                },
                callbackConversion: function (from, to) {
                    $('[name="conversion_from"]').val(from);
                    $('[name="conversion_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
