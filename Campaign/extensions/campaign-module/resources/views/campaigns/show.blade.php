@extends('campaign::layouts.app')

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
                    <a href="{{ route('campaigns.edit', ['campaign' => $campaign])  }}" class="btn palette-Cyan bg waves-effect">
                        <i class="zmdi zmdi-palette-Cyan zmdi-edit"></i> Edit
                    </a>
                    <a href="{{ route('campaigns.stats', ['campaign' => $campaign])  }}" class="btn palette-Cyan bg waves-effect">
                        <i class="zmdi zmdi-palette-Cyan zmdi-chart"></i> Stats
                    </a>
                    <a href="{{ route('campaigns.copy', ['sourceCampaign' => $campaign])  }}" class="btn palette-Cyan bg waves-effect">
                        <i class="zmdi zmdi-palette-Cyan zmdi-copy"></i> Copy
                    </a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>ID: </strong> {{ $campaign->id }}
                    </li>
                    <li class="list-group-item">
                        <strong>UUID: </strong> {{ $campaign->uuid }}
                    </li>
                    <li class="list-group-item">
                        <strong>Public ID: </strong> {{ $campaign->public_id }}
                    </li>
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
                                    <a href="{{ route('banners.edit', ['banner' => $variant['banner_id']]) }}">
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
                        <strong>Where to display: </strong>
                        <ul>
                            <li>
                                URL:
                                @if ($campaign->url_filter === 'everywhere') Everywhere
                                @elseif($campaign->url_filter === 'only_at') Only at
                                @elseif($campaign->url_filter === 'except_at') Except at
                                @endif

                                @if($campaign->url_filter !== 'everywhere')
                                <ul>
                                    @foreach($campaign->url_patterns as $urlPattern)
                                    <li><code>{{ $urlPattern }}</code></li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                            <li>
                                Referer:
                                @if ($campaign->source_filter === 'everywhere') Everywhere
                                @elseif($campaign->source_filter === 'only_at') Only at
                                @elseif($campaign->source_filter === 'except_at') Except at
                                @endif

                                @if($campaign->source_filter !== 'everywhere')
                                    <ul>
                                        @foreach($campaign->source_patterns as $sourcePattern)
                                            <li><code>{{ $sourcePattern }}</code></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        </ul>
                    </li>

                @if($campaign->pageview_rules !== null)
                    <li class="list-group-item">
                        <strong>Display banner:</strong>
                        <ul>
                            <li>
                                Display banner: @if($campaign->pageview_rules['display_banner'] === 'every')Every {{ $campaign->pageview_rules['display_banner_every'] }} page views @else Always @endif
                            </li>
                            @if($campaign->pageview_rules['display_times'])
                            <li>
                                Display to user {{ $campaign->pageview_rules['display_n_times'] }} times, then stop.
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <li class="list-group-item">
                        <strong>Display only once per session:</strong>
                        {{ @yesno($campaign->once_per_session) }}
                    </li>

                    @if($campaign->countriesWhitelist->count())
                        <li class="list-group-item">
                            <strong>Countries whitelist:</strong>
                            <ul>
                            @foreach($campaign->countriesWhitelist as $country)
                                <li>{{ $country->name }}</li>
                            @endforeach
                            </ul>
                        </li>
                    @elseif($campaign->countriesBlacklist->count())
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
                            {{ @yesno(in_array($device, $campaign->devices)) }}
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
