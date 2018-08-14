@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div id="dashboard">
        <dashboard-root :articles-url="articlesUrl">
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
                    articlesUrl: "{!! route('dashboard.articles.json') !!}"
                }
            }
        })
    </script>

@endsection
