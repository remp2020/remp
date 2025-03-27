@php

/* @var $campaign \Remp\CampaignModule\Campaign */
/* @var $banners \Illuminate\Support\Collection */
/* @var $segments \Illuminate\Support\Collection */

$banners = $banners->map(function(\Remp\CampaignModule\Banner $banner) {
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
    var campaign = {{ Illuminate\Support\Js::from([
        "name" => $campaign->name,
        "action" => $action,
        "segments" => isset($selectedSegments) ? $selectedSegments : $campaign->segments,
        "bannerId" => $bannerId,
        "variants" => $variants,
        "signedIn" => $campaign->signed_in,
        "usingAdblock" => $campaign->using_adblock,
        "oncePerSession" => $campaign->once_per_session,
        "active" => $campaign->active,
        "pageviewRules" => $campaign->pageview_rules ?? [],
        "pageviewAttributes" => $campaign->pageview_attributes ?? [],
        "countries" => $selectedCountries,
        "languages" => $selectedLanguages,
        "countriesBlacklist" => $countriesBlacklist ?? 0,
        "allDevices" => $campaign->getAllDevices(),
        "availableOperatingSystems" => $campaign->getAvailableOperatingSystems(),
        "selectedDevices" => $campaign->devices ?? [],
        "selectedOperatingSystems" => $campaign->operating_systems ?? [],
        "validateUrl" => route('campaigns.validateForm'),
        "urlFilterTypes" => $campaign->getAllUrlFilterTypes(),
        "sourceFilterTypes" => $campaign->getAllSourceFilterTypes(),
        "urlFilter" => $campaign->url_filter,
        "urlPatterns" => $campaign->url_patterns,
        "sourceFilter" => $campaign->source_filter,
        "sourcePatterns" => $campaign->source_patterns,
        "statsLink" => $campaign->id ? route('campaigns.stats', $campaign) : null,
        "editLink" => $campaign->id ? route('campaigns.edit', $campaign) : null,
        "showLink" => $campaign->id ? route('campaigns.show', $campaign) : null,
        "copyLink" => $campaign->id ? route('campaigns.copy', $campaign) : null,
        "prioritizeBannersSamePosition" => Config::get('banners.prioritize_banners_on_same_position', false),

        "banners" => $banners,
        "availableSegments" => $segments,
        "addedSegment" => null,
        "removedSegments" => [],
        "segmentMap" => $segmentMap,
        "eventTypes" => [
            [
                "category" => "banner",
                "action" => "show",
                "value" => "banner|show",
                "label" => "banner / show"
            ],
            [
                "category" => "banner",
                "action" => "click",
                "value" => "banner|click",
                "label" => "banner / click"
            ]
        ],
        "availableCountries" => $availableCountries,
        "availableLanguages" => $availableLanguages,
        "countriesBlacklistOptions" => [
            [
                "value" => 0,
                "label" => "Whitelist"
            ],
            [
                "value" => 1,
                "label" => "Blacklist"
            ]
        ],
        "activationMode" => "activate-now",
    ]) }};

    remplib.campaignForm.bind("#campaign-form", campaign);
</script>

@endpush
