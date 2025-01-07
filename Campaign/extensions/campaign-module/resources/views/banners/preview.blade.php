{{--<script type="text/javascript">--}}

@if(is_null($banner))
var bannerUuid = null;
@else
var bannerUuid = '{{ $banner->uuid }}';
var bannerPublicId = '{{ $banner->public_id }}';
var bannerId = 'b-' + bannerUuid;
var bannerJsonData = {!! $banner->toJson() !!};
@endif

var variantUuid = '{{ $variantUuid }}';
var variantPublicId = '{{ $variantPublicId }}';
var campaignUuid = '{{ $campaignUuid }}';
var campaignPublicId = '{{ $campaignPublicId }}';
var isControlGroup = {{ $controlGroup }};
var scripts = [];
if (typeof window.remplib.banner === 'undefined') {
    scripts.push('{{ asset(mix('/js/banner.js', '/assets/lib')) }}');
}

var styles = [];

var waiting = scripts.length + styles.length;
var run = function() {
    if (waiting) {
        return;
    }

    var banner = {};
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var colorSchemes = JSON.parse('{!! json_encode($colorSchemes) !!}');

    if (!isControlGroup) {
        var banner = remplib.banner.fromModel(bannerJsonData);
    }

    banner.show = false;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;
    banner.colorSchemes = colorSchemes;

    banner.campaignUuid = campaignUuid;
    banner.campaignPublicId = campaignPublicId;
    banner.variantUuid = variantUuid;
    banner.variantPublicId = variantPublicId;
    banner.uuid = bannerUuid;
    banner.publicId = bannerPublicId;

    if (typeof remplib.campaign.bannerUrlParams !== "undefined") {
        banner.urlParams = remplib.campaign.bannerUrlParams;
    }

    if (isControlGroup) {
        banner.displayDelay = 0;
        banner.displayType = 'none';
    } else {
        var d = document.createElement('div');
        d.id = bannerId;
        var bp = document.createElement('banner-preview');
        d.appendChild(bp);

        var target = null;
        if (banner.displayType == 'inline') {
            target = document.querySelector(banner.targetSelector);
            if (target === null) {
                console.warn("REMP: unable to display banner, selector not matched: " + banner.targetSelector);
                return;
            }
        } else {
            target = document.getElementsByTagName('body')[0];
        }
        target.appendChild(d);

        remplib.banner.bindPreview('#' + bannerId, banner);
    }

    setTimeout(function() {
        if (!banner.manualEventsTracking) {
            remplib.tracker.trackEvent("banner", "show", null, null, {
                "rtm_source": "remp_campaign",
                "rtm_medium": banner.displayType,
                "rtm_campaign": banner.campaignUuid,
                "rtm_content": banner.uuid,
                "rtm_variant": banner.variantUuid
            });
        }

        banner.show = true;
        if (banner.closeTimeout) {
            setTimeout(function() {
                banner.show = false;
            }, banner.closeTimeout);
        }

        if (typeof resolve !== "undefined") {
            resolve(true);
        }

        remplib.campaign.handleBannerDisplayed(banner.campaignUuid, banner.uuid, banner.variantUuid, banner.campaignPublicId, banner.publicId, banner.variantPublicId);
    }, banner.displayDelay);
};

for (var i=0; i<scripts.length; i++) {
    remplib.loadScript(scripts[i], function() {
        waiting -= 1;
        run();
    });
}
for (i=0; i<styles.length; i++) {
    remplib.loadStyle(styles[i], function() {
        waiting -= 1;
        run();
    });
}

{{--</script>--}}
