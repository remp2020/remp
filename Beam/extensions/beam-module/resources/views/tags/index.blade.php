@extends('beam::layouts.app')

@section('title', 'Tags')

@section('content')

    <div class="c-header">
        <h2>Tags</h2>
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
                {{ html()->hidden('conversion_to', $conversionTo) }}
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
            <h2>Tag stats <small></small></h2>
        </div>

        @include('beam::tags.subviews.dt_tags', ['dataSource' => route('tags.dtTags')])
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
