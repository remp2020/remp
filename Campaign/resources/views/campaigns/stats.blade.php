@extends('layouts.app')

@section('title', $campaign->name . ' stats')

@section('content')
    <div id="stats-app"></div>
@endsection

@push('scripts')
    <script>
        var campaign = {
            "id": {{ $campaign->id }},
            "name": '{!! $campaign->name !!}' || null,
            "variants": {!! @json($campaign->campaignBanners) !!},
        };

        remplib.campaignStats.bind("#stats-app", campaign)
    </script>
@endpush

