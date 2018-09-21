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
        <campaign-stats-root
            :url="url"
            :name="name"
            :variants="variants"
            :variant-banner-links="variantBannerLinks"
            :from="from"
            :to="to"
            :timezone="timezone"
        ></campaign-stats-root>
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
                CampaignStatsRoot
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
                    name: "<a href=\"{!! route('campaigns.show', ['campaign' => $campaign]) !!}\">{{ $campaign->name }}</a>",
                    url: "{!! route('campaigns.stats.data', $campaign->id) !!}",
                    variants: {!! @json($variants) !!},
                    variantBannerLinks: {!! @json($variantBannerLinks) !!},
                    from: '{!! $from !!}',
                    to: '{!! $to !!}',
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                }
            }
        })
    </script>
@endsection
