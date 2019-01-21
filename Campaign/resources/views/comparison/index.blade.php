@extends('layouts.app')

@section('title', 'Campaigns comparison')

@section('content')
    <div class="c-header">
        <h2>CAMPAIGNS COMPARISON</h2>
    </div>

    <div class="card" id="comparison-app">
        <campaign-comparison base-url="{!! route('comparison.json') !!}">
        </campaign-comparison>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#comparison-app",
            components: {
                CampaignComparison
            }
        });
    </script>

@endsection
