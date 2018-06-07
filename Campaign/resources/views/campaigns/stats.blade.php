@extends('layouts.app')

@section('title', $campaign->name . ' stats')

@section('content')
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

    <div id="stats-app">
        <campaign-stats
            :id="{{ $campaign->id }}"
            :name="'{!! $campaign->name !!}'"
            :variants="variants"
            :from="'{!! $publishedFrom !!}'"
            :to="'{!! $publishedTo !!}'"
        ></campaign-stats>
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

        new Vue({
            el: "#stats-app",
            components: {
                CampaignStats
            },
            data() {
                return {
                    variants: {!! @json($campaign->campaignBanners) !!}
                }
            }
        })
    </script>
@endsection
