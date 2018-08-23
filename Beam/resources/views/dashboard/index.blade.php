@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div id="dashboard">
        <dashboard-root :articles-url="articlesUrl" :time-histogram-url="timeHistogramUrl">
        </dashboard-root>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#dashboard",
            components: {
                DashboardRoot
            },
            data: function() {
                return {
                    articlesUrl: "{!! route('dashboard.articles.json') !!}",
                    timeHistogramUrl: "{!! route('dashboard.timeHistogram.json') !!}"
                }
            }
        })
    </script>

@endsection
