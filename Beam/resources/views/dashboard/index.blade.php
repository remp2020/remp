@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div id="dashboard">
        <div class="c-header">
            <h2>Live BEAM Dashboard</h2>
        </div>

        <dashboard-root
                :articles-url="articlesUrl"
                :time-histogram-url="timeHistogramUrl"
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
                    enableFrontpageFiltering: this.enableFrontpageFiltering
                }
            },
            store: DashboardStore,
            data: {
                articlesUrl: "{!! route('dashboard.articles.json') !!}",
                timeHistogramUrl: "{!! route('dashboard.timeHistogram.json') !!}",
                enableFrontpageFiltering: {{ $enableFrontpageFiltering ? 'true' : 'false' }},
                options: {!! json_encode($options) !!}
            }
        })
    </script>

@endsection
