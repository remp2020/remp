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

$availableCountries = $availableCountries->map(function(\App\Country $country) {
   return ['value' => $country->iso_code, 'label' => $country->name];
});

$selectedCountries = $campaign->countries->map(function(\App\Country $country) {
   return $country->iso_code;
});

$countriesBlacklist = 0;
foreach ($campaign->countries as $country) {
    $countriesBlacklist = (int) $country->pivot->blacklisted;
}

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
        "bannerId": {!! @json($campaign->banner ? $campaign->banner->id : null) !!},
        "altBannerId": {!! @json($campaign->altBanner ? $campaign->altBanner->id : null) !!},
        "signedIn": {!! @json($campaign->signed_in) !!},
        "oncePerSession": {!! @json($campaign->once_per_session) !!},
        "active": {!! @json($campaign->active) !!},
        "pageviewRules": {!! @json($campaign->pageview_rules) !!} || [],
        "countries": {!! $selectedCountries->toJson(JSON_UNESCAPED_UNICODE) !!},
        "countriesBlacklist": {!! @json($countriesBlacklist ?? 0) !!},
        "allDevices": {!! @json($campaign->getAllDevices()) !!},
        "selectedDevices": {!! @json($campaign->devices) !!} || [],

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
    };
    remplib.campaignForm.bind("#campaign-form", campaign);
</script>

@endpush
