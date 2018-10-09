@extends('layouts.simple')

@section('title', 'Public dashboard')

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
                    articlesUrl: "{!! route('dashboard.public.articles.json') !!}",
                    timeHistogramUrl: "{!! route('dashboard.public.timeHistogram.json') !!}"
                }
            }
        })
    </script>

@endsection
