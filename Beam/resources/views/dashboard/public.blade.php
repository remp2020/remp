@extends('layouts.simple')

@section('title', 'Public Dashboard')

@section('content')

    <div id="dashboard">
        <dashboard-root
                :options="options"
                :articles-url="articlesUrl"
                :time-histogram-url="timeHistogramUrl"
                :time-histogram-url-new="timeHistogramUrlNew"
                :account-property-tokens="accountPropertyTokens"
                :csrf-token="csrfToken"
                :external-events="externalEvents"
                :conversion-rate-multiplier="conversionRateMultiplier">
        </dashboard-root>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#dashboard",
            components: {
                DashboardRoot
            },
            created: function() {
                this.$store.commit('changeSettings', {
                    newGraph: {{ json_encode(config('beam.pageview_graph_data_source') === 'snapshots') }}
                })
            },
            provide: function() {
                return {
                    dashboardOptions: this.options
                }
            },
            store: DashboardStore,
            data: {
                articlesUrl: "{!! route('public.articles.json') !!}",
                timeHistogramUrl: "{!! route('public.timeHistogram.json') !!}",
                timeHistogramUrlNew: "{!! route('public.timeHistogramNew.json') !!}",
                options: {!! json_encode($options) !!},
                accountPropertyTokens: {!! json_encode($accountPropertyTokens ?? false) !!},
                csrfToken: {!!'"' . csrf_token() . '"'!!},
                externalEvents: {!! json_encode($externalEvents) !!},
                conversionRateMultiplier: {!! $conversionRateMultiplier !!}
            }
        })
    </script>

@endsection
