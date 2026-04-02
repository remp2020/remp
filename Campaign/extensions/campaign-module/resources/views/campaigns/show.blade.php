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
                        {{ $campaign->signedInLabel() }}
                    </li>

                    <li class="list-group-item">
                        <strong>User ad-blocking state:</strong>
                        {{ $campaign->usingAdblockLabel() }}
                    </li>

                    @if(!empty($campaign->pageview_attributes))
                    <li class="list-group-item">
                        <strong>Pageview attributes:</strong>
                        <ul>
                        @php $opLabels = ['=' => 'is', '!=' => 'is not']; @endphp
                        @foreach($campaign->pageview_attributes as $attribute)
                            <li><code>{{ $attribute['name'] }}</code> {{ $opLabels[$attribute['operator']] ?? $attribute['operator'] }} <code>{{ $attribute['value'] }}</code></li>
                        @endforeach
                        </ul>
                    </li>
                    @endif

                    <li class="list-group-item">
                        <strong>Segments: </strong>
                        @if($campaign->segments->isEmpty())
                            None
                        @else
                            <ul>
                            @foreach($campaign->segments as $segment)
                                <li>
                                    @if($segment->inclusive)
                                        <i class="zmdi zmdi-eye" style="color: #009688" title="User needs to be member of segment to see the campaign."></i>
                                    @else
                                        <i class="zmdi zmdi-eye-off" style="color: #f44336" title="User must not be member of segment to see the campaign."></i>
                                    @endif
                                    {{ $segment->code }}
                                </li>
                            @endforeach
                            </ul>
                        @endif
                    </li>

                    <li class="list-group-item">
                        <strong>Where to display: </strong>
                        <ul>
                            <li>
                                URL:
                                {{ $campaign->getAllUrlFilterTypes()[$campaign->url_filter] ?? $campaign->url_filter }}

                                @if($campaign->url_filter !== 'everywhere')
                                <ul>
                                    @foreach($campaign->url_patterns as $urlPattern)
                                    <li><code>{{ $urlPattern }}</code></li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                            <li>
                                Traffic source:
                                {{ $campaign->getAllSourceFilterTypes()[$campaign->source_filter] ?? $campaign->source_filter }}

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
                            @foreach([
                                ['key' => 'after_banner_closed_display', 'label' => 'After banner closed', 'hours_key' => 'after_closed_hours'],
                                ['key' => 'after_banner_clicked_display', 'label' => 'After banner clicked', 'hours_key' => 'after_clicked_hours'],
                            ] as $rule)
                                @if(!empty($campaign->pageview_rules[$rule['key']]))
                                <li>
                                    {{ $rule['label'] }}:
                                    @php $val = $campaign->pageview_rules[$rule['key']]; @endphp
                                    @if($val === 'always') Always show
                                    @elseif($val === 'never') Never show it again
                                    @elseif($val === 'never_in_session') Don't show within current session
                                    @elseif($val === 'close_for_hours') Don't show for the next {{ $campaign->pageview_rules[$rule['hours_key']] }} hours
                                    @endif
                                </li>
                                @endif
                            @endforeach
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

                    @if(!empty($campaign->languages))
                        <li class="list-group-item">
                            <strong>Languages:</strong>
                            <ul>
                            @foreach($campaign->languages as $language)
                                <li>{{ \Locale::getDisplayLanguage($language) }}</li>
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

                    @if(!empty($campaign->operating_systems))
                        @php
                            $osLabels = collect($campaign->getAvailableOperatingSystems())->pluck('label', 'value');
                        @endphp
                        <li class="list-group-item">
                            <strong>Operating systems:</strong>
                            <ul>
                            @foreach($campaign->operating_systems as $os)
                                <li>{{ $osLabels[$os] ?? $os }}</li>
                            @endforeach
                            </ul>
                        </li>
                    @endif

                    @php
                        $activeSchedules = $campaign->schedules->filter(fn($s) => !$s->isStopped() && !$s->isPaused());
                    @endphp
                    @if($activeSchedules->isNotEmpty())
                        <li class="list-group-item">
                            <strong>Schedules:</strong>
                            <ul>
                            @foreach($activeSchedules as $schedule)
                                <li>
                                    {{ $schedule->start_time->format('Y-m-d H:i') }}
                                    &mdash;
                                    {{ $schedule->end_time ? $schedule->end_time->format('Y-m-d H:i') : 'open-ended' }}
                                    ({{ $schedule->status }})
                                </li>
                            @endforeach
                            </ul>
                        </li>
                    @endif

                    <li class="list-group-item">
                        <strong>Active: </strong>{{ @yesno($campaign->active) }}
                    </li>

                </ul>
            </div>
        </div>
    </div>
@endsection
