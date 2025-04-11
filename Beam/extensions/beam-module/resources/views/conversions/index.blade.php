@extends('beam::layouts.app')

@section('title', 'Conversions')

@section('content')

    <div class="c-header">
        <h2>Conversions</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by conversion date</h4>
                <div id="smart-range-selector">
                    {{ html()->hidden('conversion_from', $conversionFrom) }}
                    {{ html()->hidden('conversion_to', $conversionTo) }}
                    <smart-range-selector from="{{$conversionFrom}}" to="{{$conversionTo}}" :callback="callback">
                    </smart-range-selector>
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
                'article.title' => [
                    'header' => 'article',
                    'orderable' => false,
                    'priority' => 1,
                    'render' => 'link'
                ],
                'content_type' => [
                    'header' => 'Type',
                    'orderable' => false,
                    'filter' => $contentTypes,
                    'priority' => 2,
                    'visible' => count($contentTypes) > 1,
                ],
                'article.authors[, ].name' => [
                    'header' => 'authors',
                    'orderable' => false,
                    'filter' => $authors,
                    'priority' => 2,
                ],
                'article.sections[, ].name' => [
                    'header' => 'sections',
                    'orderable' => false,
                    'filter' => $sections,
                    'priority' => 4,
                ],
                'article.tags[, ].name' => [
                    'header' => 'tags',
                    'orderable' => false,
                    'filter' => $tags,
                    'priority' => 5,
                ],
                'amount' => [
                    'header' => 'amount',
                    'render' => 'number',
                    'priority' => 1,
                    'orderSequence' => ['desc', 'asc'],
                    'className' => 'text-right',
                ],
                'currency' => [
                    'header' => 'currency',
                    'orderable' => false,
                    'priority' => 3,
                ],
                'paid_at' => [
                    'header' => 'paid at',
                    'render' => 'date',
                    'priority' => 2,
                ],
            ],
            'dataSource' => route('conversions.json'),
            'rowActions' => [
                ['name' => 'show', 'class' => 'zmdi-palette-Cyan zmdi-info-outline', 'title' => 'Show conversions'],
            ],
            'rowActionLink' => 'show',
            'order' => [6, 'desc'],
            'requestParams' => [
                'conversion_from' => '$(\'[name="conversion_from"]\').val()',
                'conversion_to' => '$(\'[name="conversion_to"]\').val()',
                'tz' => 'Intl.DateTimeFormat().resolvedOptions().timeZone'
            ],
            'refreshTriggers' => [
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_from"]'
                ],
                [
                    'event' => 'change',
                    'selector' => '[name="conversion_to"]',
                ],
            ],
            'exportColumns' => [0,1,2,3,4,5],
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
                    $('[name="conversion_from"]').val(from);
                    $('[name="conversion_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
