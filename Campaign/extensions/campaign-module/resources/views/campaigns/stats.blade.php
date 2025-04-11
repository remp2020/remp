@extends('campaign::layouts.app')

@section('title', $campaign->name . ' stats')

@section('content')
    @if($beamJournalConfigured)
        <div class="well">
            <div class="row">
                <div class="col-md-6">
                    <h4>Filter by date and time</h4>
                    <div id="smart-range-selector">
                        {{ html()->hidden('from', $from) }}
                        {{ html()->hidden('to', $to) }}
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
                        :edit-link="editLink"
                        :variants="variants"
                        :variant-banner-links="variantBannerLinks"
                        :variant-banner-texts="variantBannerTexts"
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
                        editLink: "{!! route('campaigns.edit', $campaign) !!}",
                        variants: {!! @json($variants) !!},
                        variantBannerLinks: {!! @json($variantBannerLinks) !!},
                        variantBannerTexts: {!! @json($variantBannerTexts) !!},
                        from: '{!! $from !!}',
                        to: '{!! $to !!}',
                        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                    }
                }
            })
        </script>
    @else
        <div class="card">
            <div class="row">
                <div class="col-md-12 m-l-30 m-r-30 m-t-25">
                        <p>No stats are available for the campaign, since Beam Journal integration is not configured.</p>
                        <p>Information on how to configure Beam Journal integration can be found in <a href="https://github.com/remp2020/remp/tree/master/Campaign#admin-integration-with-beam-journal">the documentation</a>.</p>
                </div>
            </div>
        </div>
    @endif
@endsection
