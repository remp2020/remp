@extends('beam::layouts.app')

@section('title', 'Google Analytics Reporting')

@section('content')
    <div class="c-header">
        <h2>Google Analytics Reporting</h2>
    </div>

    <div class="card card-chart" id="google-analytics-reporting-graph">
        <google-analytics-reporting-histogram ref="histogram" :url="url">
        </google-analytics-reporting-histogram>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#google-analytics-reporting-graph",
            components: {
                GoogleAnalyticsReportingHistogram
            },
            created: function() {
                document.addEventListener('visibilitychange', this.visibilityChanged)
            },
            beforeDestroy: function() {
                document.removeEventListener('visibilitychange', this.visibilityChanged)
            },
            methods: {
                visibilityChanged: function() {
                    if (document.visibilityState === 'visible') {
                        this.$refs["histogram"].reload()
                    }
                }
            },
            data: function() {
                return {
                    url: "{!! route('googleanalyticsreporting.timeHistogram.json') !!}"
                }
            }
        })
    </script>

@endsection
