@extends('layouts.app')

@section('title', 'Show campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns: {{ $campaign->name }}</h2>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Settings</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Banner: </strong>{{ link_to_route('banners.show', $campaign->banner->name, $campaign->banner) }}
                    </li>
                    <li class="list-group-item">
                        <strong>Banner B: </strong>{{ link_to_route('banners.show', $campaign->altBanner->name, $campaign->altBanner) }}
                    </li>
                    <li class="list-group-item">
                        <strong>Segments: </strong>
                        <ul>
                        @foreach($campaign->segments as $segment)
                            <li>{{ $segment->code }}</li>
                        @endforeach
                        </ul>
                    </li>
                    <li class="list-group-item">
                        <strong>Active: </strong>{{ @yesno($campaign->active) }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
