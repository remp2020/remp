@extends('layouts.app')

@section('title', 'Campaigns comparison')

@section('content')
    <div class="c-header">
        <h2>CAMPAIGNS COMPARISON</h2>
    </div>

    <div class="card" id="comparison-app">
        <campaign-comparison-root base-url="{!! route('comparison.json') !!}">
        </campaign-comparison-root>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#comparison-app",
            components: {
                CampaignComparisonRoot
            }
        });
    </script>

@endsection
