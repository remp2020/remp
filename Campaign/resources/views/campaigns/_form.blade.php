@php

/* @var $campaign \App\Campaign */
/* @var $banners \Illuminate\Support\Collection */
/* @var $segments \Illuminate\Support\Collection */

$banners = $banners->map(function(\App\Banner $banner) {
   return ['id' => $banner->id, 'name' => $banner->name];
});

$segments = $segments->mapToGroups(function ($item) {
    return [$item->group->name => [$item->code => $item]];
})->mapWithKeys(function($item, $key) {
    return [$key => $item->collapse()];
});

$segmentMap = $segments->flatten()->mapWithKeys(function ($item) {
    return [$item->code => $item->name];
});

@endphp

<div id="campaign-form">
    <campaign-form></campaign-form>
</div>

@push('scripts')

<script type="text/javascript">
    var campaign = {
        "name": '{!! $campaign->name !!}' || null,
        "action": '{{ $action }}',
        "segments": {!! isset($selectedSegments) ? $selectedSegments->toJson(JSON_UNESCAPED_UNICODE) : $campaign->segments->toJson(JSON_UNESCAPED_UNICODE) !!},
        "bannerId": {!! @json($bannerId) !!},
        "variants": {!! @json($variants) !!},
        "signedIn": {!! @json($campaign->signed_in) !!},
        "usingAdblock": {!! @json($campaign->using_adblock) !!},
        "oncePerSession": {!! @json($campaign->once_per_session) !!},
        "active": {!! @json($campaign->active) !!},
        "pageviewRules": {!! @json($campaign->pageview_rules) !!} || [],
        "pageviewAttributes": {!! @json($campaign->pageview_attributes) !!} || [],
        "countries": {!! @json($selectedCountries) !!},
        "languages": {!! @json($selectedLanguages) !!},
        "countriesBlacklist": {!! @json($countriesBlacklist ?? 0) !!},
        "allDevices": {!! @json($campaign->getAllDevices()) !!},
        "selectedDevices": {!! @json($campaign->devices) !!} || [],
        "validateUrl": {!! @json(route('campaigns.validateForm')) !!},
        "urlFilterTypes": {!! @json($campaign->getAllUrlFilterTypes()) !!},
        "sourceFilterTypes": {!! @json($campaign->getAllSourceFilterTypes()) !!},
        "urlFilter": {!! @json($campaign->url_filter) !!},
        "urlPatterns": {!! @json($campaign->url_patterns) !!},
        "sourceFilter": {!! @json($campaign->source_filter) !!},
        "sourcePatterns": {!! @json($campaign->source_patterns) !!},
        "statsLink": '{!! $campaign->id ? route('campaigns.stats', $campaign) : null !!}',
        "editLink": '{!! $campaign->id ? route('campaigns.edit', $campaign) : null !!}',
        "showLink": '{!! $campaign->id ? route('campaigns.show', $campaign) : null !!}',
        "prioritizeBannersSamePosition":'{{ Config::get('banners.prioritize_banners_on_same_position', false) }}',

        "banners": {!! $banners->toJson(JSON_UNESCAPED_UNICODE) !!},
        "availableSegments": {!! $segments->toJson(JSON_UNESCAPED_UNICODE) !!},
        "addedSegment": null,
        "removedSegments": [],
        "segmentMap": {!! $segmentMap->toJson(JSON_UNESCAPED_UNICODE) !!},
        "eventTypes": [
            {
                "category": "banner",
                "action": "show",
                "value": "banner|show",
                "label": "banner / show"
            },
            {
                "category": "banner",
                "action": "click",
                "value": "banner|click",
                "label": "banner / click"
            }
        ],
        "availableCountries": {!! $availableCountries->toJson(JSON_UNESCAPED_UNICODE) !!},
        "availableLanguages": {!! $availableLanguages !!},
        "countriesBlacklistOptions": [
            {
                "value": 0,
                "label": "Whitelist"
            },
            {
                "value": 1,
                "label": "Blacklist"
            }
        ],
        "activationMode": "activate-now",
    }
    remplib.campaignForm.bind("#campaign-form", campaign);
</script>

@endpush
