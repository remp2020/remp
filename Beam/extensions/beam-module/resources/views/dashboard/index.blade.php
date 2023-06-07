@extends('beam::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div id="dashboard">
        <div class="c-header">
            <h2>Live Dashboard</h2>
        </div>

        <dashboard-root
                :articles-url="articlesUrl"
                :time-histogram-url="timeHistogramUrl"
                :time-histogram-url-new="timeHistogramUrlNew"
                :conversion-rate-multiplier="conversionRateMultiplier"
                :external-events="externalEvents"
                :options="options">
        </dashboard-root>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#dashboard",
            components: {
                DashboardRoot
            },
            provide: function() {
                return {
                    dashboardOptions: this.options
                }
            },
            created: function() {
                this.$store.commit('changeSettings', {
                    newGraph: {{ @json(config('beam.pageview_graph_data_source') === 'snapshots') }}
                });
            },
            store: DashboardStore,
            data: {
                articlesUrl: "{!! route('dashboard.articles.json') !!}",
                timeHistogramUrl: "{!! route('dashboard.timeHistogram.json') !!}",
                timeHistogramUrlNew: "{!! route('dashboard.timeHistogramNew.json') !!}",
                options: {!! @json($options) !!},
                externalEvents: {!! @json($externalEvents) !!},
                conversionRateMultiplier: {!! $conversionRateMultiplier !!}
            }
        })
    </script>

@endsection
