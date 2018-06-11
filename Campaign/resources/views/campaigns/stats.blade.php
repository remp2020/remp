@extends('layouts.app')

@section('title', $campaign->name . ' stats')

@section('content')
    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by date and time</h4>
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
            :from="from"
            :to="to"
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
                    $('[name="published_from"]').val(from).trigger("change");
                    $('[name="published_to"]').val(to).trigger("change");
                }
            }
        });

        new Vue({
            el: "#stats-app",
            components: {
                CampaignStats
            },
            mounted: function() {
                var vm = this;

                $('[name="published_from"]').on('change', function () {
                    vm.from = $('[name="published_from"]').val();
                });

                $('[name="published_to"]').on('change', function () {
                    vm.to = $('[name="published_to"]').val();
                });
            },
            data() {
                return {
                    variants: {!! @json($campaign->campaignBanners) !!},
                    from: '{!! $publishedFrom !!}',
                    to: '{!! $publishedTo !!}'
                }
            }
        })
    </script>
@endsection
