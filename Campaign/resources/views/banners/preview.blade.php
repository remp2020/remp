{{--<script type="text/javascript">--}}

@if(is_null($banner))
var bannerUuid = null;
@else
var bannerUuid = '{{ $banner->uuid }}';
var bannerId = 'b-' + bannerUuid;
var bannerJsonData = {!! $banner->toJson() !!};
@endif

var variantUuid = '{{ $variantUuid }}';
var campaignUuid = '{{ $campaign->uuid }}';
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

    if (!isControlGroup) {
        var banner = remplib.banner.fromModel(bannerJsonData);
    }

    banner.show = false;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    banner.campaignUuid = campaignUuid;
    banner.variantUuid = variantUuid;
    banner.uuid = bannerUuid;

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
        remplib.tracker.trackEvent("banner", "show", null, null, {
            "utm_source": "remp_campaign",
            "utm_medium": banner.displayType,
            "utm_campaign": banner.campaignUuid,
            "utm_content": banner.uuid,
            "banner_variant": banner.variantUuid
        });
        banner.show = true;
        if (banner.closeTimeout) {
            setTimeout(function() {
                banner.show = false;
            }, banner.closeTimeout);
        }
        remplib.campaign.storeCampaignDetails(banner.campaignUuid, banner.uuid, banner.variantUuid);
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
