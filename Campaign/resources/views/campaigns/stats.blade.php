@extends('layouts.app')

@section('title', $campaign->name . ' stats')

@section('content')
    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by date and time</h4>
                <div id="smart-range-selector">
                    {!! Form::hidden('from', $from) !!}
                    {!! Form::hidden('to', $to) !!}
                    <smart-range-selector from="{{$from}}" to="{{$to}}" :callback="callback">
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
                    $('[name="from"]').val(from).trigger("change");
                    $('[name="to"]').val(to).trigger("change");
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

                $('[name="from"]').on('change', function () {
                    vm.from = $('[name="from"]').val();
                });

                $('[name="to"]').on('change', function () {
                    vm.to = $('[name="to"]').val();
                });
            },
            data() {
                return {
                    variants: {!! @json($campaign->campaignBanners) !!},
                    from: '{!! $from !!}',
                    to: '{!! $to !!}'
                }
            }
        })
    </script>
@endsection
