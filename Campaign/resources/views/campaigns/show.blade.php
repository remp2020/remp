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

                <div class="actions">
                    <a href="{{ route('campaigns.edit', ['campaign' => $campaign])  }}" class="btn palette-Cyan bg waves-effect">Edit</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Variants:</strong>
                        <ul>
                        @foreach($campaign->campaignBanners as $variant)
                            @if($variant->control_group)
                                <li>
                                    Control Group ({{ $variant->proportion }}%)
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('banners.edit', $variant['banner_id']) }}">
                                        {{ $variant->banner['name'] }} ({{ $variant->proportion }}%)
                                    </a>
                                </li>
                            @endif
                        @endforeach
                        </ul>
                    </li>

                    <li class="list-group-item">
                        <strong>User signed-in state:</strong>
                        @if($campaign->signed_in === null)
                            Everyone
                        @elseif($campaign->signed_in === true)
                            Only signed in
                        @elseif ($campaign->signed_in === false)
                            Only anonymous
                        @endif
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
                        <strong>Display only once per session:</strong>
                        {{ @yesno($campaign->once_per_session) }}
                    </li>

                    @if(count($campaign->pageview_rules))
                    <li class="list-group-item">
                        <strong>Pageview rules:</strong>
                        <ul>
                            @foreach($campaign->pageview_rules as $rule)
                            <li>
                                {{ ucfirst($rule['rule']) }}
                                {{ ucfirst($rule['num']) }}
                                request
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endif

                    @if($campaign->countriesWhitelist)
                        <li class="list-group-item">
                            <strong>Countries whitelist:</strong>
                            <ul>
                            @foreach($campaign->countriesWhitelist as $country)
                                <li>{{ $country->name }}</li>
                            @endforeach
                            </ul>
                        </li>
                    @elseif($campaign->countriesBlacklist)
                        <li class="list-group-item">
                            <strong>Countries blacklist:</strong>
                            <ul>
                            @foreach($campaign->countriesBlacklist as $country)
                                <li>{{ $country->name }}</li>
                            @endforeach
                            </ul>
                        </li>
                    @endif

                    @foreach(['desktop', 'mobile'] as $device)
                        <li class="list-group-item">
                            <strong>Show on {{ ucfirst($device) }}:</strong>
                            @if(in_array($device, $campaign->devices))
                                Yes
                            @else
                                No
                            @endif
                        </li>
                    @endforeach

                    <li class="list-group-item">
                        <strong>Active: </strong>{{ @yesno($campaign->active) }}
                    </li>

                </ul>
            </div>
        </div>
    </div>
@endsection
