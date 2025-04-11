@extends('beam::layouts.app')

@section('title', 'Sections')

@section('content')

    <div class="c-header">
        <h2>Sections</h2>
    </div>

    <div class="well">
        <div id="smart-range-selectors" class="row">
            <div class="col-md-4">
                <h4>Filter by publish date</h4>
                {{ html()->hidden('published_from', $publishedFrom) }}
                {{ html()->hidden('published_to', $publishedTo) }}
                <smart-range-selector from="{{$publishedFrom}}" to="{{$publishedTo}}" :callback="callbackPublished">
                </smart-range-selector>
            </div>

            <div class="col-md-4">
                <h4>Filter by conversion date</h4>
                {{ html()->hidden('conversion_from', $conversionFrom) }}
                {{ html()->hidden('conversion_to', $publishedTo) }}
                <smart-range-selector from="{{$conversionFrom}}" to="{{$conversionTo}}" :callback="callbackConversion">
                </smart-range-selector>
            </div>

            <div class="col-md-2">
                <h4>Filter by article content type</h4>
                {{ html()->hidden('content_type', $contentType) }}

                <v-select
                        name="content_type_select"
                        :options="contentTypes"
                        value="{{$contentType}}"
                        title="all"
                        liveSearch="false"
                        v-on:input="callbackContentType"
                ></v-select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Section stats <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'header' => 'section',
                    'orderable' => false,
                    'filter' => $sections,
                    'priority' => 1,
                    'render' => 'link',
                ],
                'articles_count' => [
                    'header' => 'articles',
                    'priority' => 3,
                    'searchable' => false,
                    'orderSequence' => ['desc', 'asc'],
                    'render' => 'number',
                    'className' => 'text-right'
                ],
                'conversions_count' => [
                    'header' => 'conversions',
                    'priority' => 2,
                    'searchable' => false,
                    'orderSequence' => ['desc', 'asc'],
                    'render' => 'number',
                    'className' => 'text-right'
                ],
                'conversions_amount' => [
                    'header' => 'amount',
                    'render' => 'array',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_all' => [
                    'header' => 'all pageviews',
                    'render' => 'number',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_not_subscribed' => [
                    'header' => 'not subscribed pageviews',
                    'render' => 'number',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'pageviews_subscribers' => [
                    'header' => 'subscriber pageviews',
                    'render' => 'number',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_all' => [
                    'header' => 'avg time all',
                    'render' => 'duration',
                    'priority' => 2,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_not_subscribed' => [
                    'header' => 'avg time not subscribed',
                    'render' => 'duration',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
                'avg_timespent_subscribers' => [
                    'header' => 'avg time subscribers',
                    'render' => 'duration',
                    'priority' => 5,
                    'orderSequence' => ['desc', 'asc'],
                    'searchable' => false,
                    'className' => 'text-right'
                ],
            ],
            'dataSource' => route('sections.dtSections'),
            'order' => [2, 'desc'],
            'requestParams' => [
                'published_from' => '$(\'[name="published_from"]\').val()',
                'published_to' => '$(\'[name="published_to"]\').val()',
                'conversion_from' => '$(\'[name="conversion_from"]\').val()',
                'conversion_to' => '$(\'[name="conversion_to"]\').val()',
                'content_type' => '$(\'[name="content_type"]\').val()',
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
                [
                    'event' => 'change',
                    'selector' => '[name="content_type"]',
                ],
            ],
            'exportColumns' => [0,1,2,3,4,5,6,7,8,9],
        ]) !!}
    </div>

    <script type="text/javascript">
      new Vue({
        el: "#smart-range-selectors",
        components: {
          SmartRangeSelector,
          vSelect
        },
        data: function () {
          return {
            contentTypes: {!! @json($contentTypes) !!}
          }
        },
        methods: {
          callbackPublished: function (from, to) {
            $('[name="published_from"]').val(from);
            $('[name="published_to"]').val(to).trigger("change");
          },
          callbackConversion: function (from, to) {
            $('[name="conversion_from"]').val(from);
            $('[name="conversion_to"]').val(to).trigger("change");
          },
          callbackContentType: function (contentType) {
            $('[name="content_type"]').val(contentType).trigger("change");
          }
        }
      });
    </script>

@endsection
