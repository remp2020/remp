@extends('layouts.simple')

@section('title', 'Public dashboard')

@section('content')

    <div id="dashboard">
        <dashboard-root
                :options="options"
                :articles-url="articlesUrl"
                :time-histogram-url="timeHistogramUrl">
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
                articlesUrl: "{!! route('public.articles.json') !!}",
                timeHistogramUrl: "{!! route('public.timeHistogram.json') !!}",
                enableFrontpageFiltering: {{ $enableFrontpageFiltering ? 'true' : 'false' }},
                options: {!! json_encode($options) !!}
            }
        })
    </script>

@endsection
