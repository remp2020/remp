@extends('layouts.app')

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
                    {!! Form::hidden('conversion_from', $conversionFrom) !!}
                    {!! Form::hidden('conversion_to', $conversionTo) !!}
                    <smart-range-selector header="X" from="{{$conversionFrom}}" to="{{$conversionTo}}" :callback="callback">
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
                'article.title' => ['header' => 'article', 'orderable' => false],
                'article.authors[, ].name' => ['header' => 'authors', 'orderable' => false, 'filter' => $authors],
                'article.sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
                'amount' => ['header' => 'amount'],
                'currency' => ['header' => 'currency', 'orderable' => false],
                'paid_at' => ['header' => 'paid at', 'render' => 'date'],
            ],
            'dataSource' => route('conversions.json'),
            'order' => [5, 'desc'],
            'requestParams' => [
                'conversion_from' => '$(\'[name="conversion_from"]\').val()',
                'conversion_to' => '$(\'[name="conversion_to"]\').val()'
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
                    // Avoid double loading of data table, therefore trigger change only on single input
                    $('[name="conversion_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
