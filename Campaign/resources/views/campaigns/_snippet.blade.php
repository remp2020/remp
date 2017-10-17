@php
    $campaignUrl = url('/');
    $campaignLibUrl = asset("/assets/lib/js/remplib.js");

    $beamWebUrl = config('services.remp_beam.web_base_url');
    if ($beamWebUrl !== null) {
        $trackerUrl = url($beamWebUrl);
        $trackerLibUrl = $trackerUrl . "/assets/lib/js/remplib.js";
    }
@endphp

<script type="text/javascript">
    (function(win, doc) {
        function mock(fn) {
            return function() {
                this._.push([fn, arguments])
            }
        }
        function load(url) {
            var script = doc.createElement("script");
            script.type = "text/javascript";
            script.async = true;
            script.src = url;
            doc.getElementsByTagName("head")[0].appendChild(script);
        }
        win.remplib = win.remplib || {};
        if (!win.remplib.campaign) {
            var fn, i, funcs = "init".split(" ");
            win.remplib.campaign = {_: []};
            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib.campaign[fn] = mock(fn);
            }
        }
        if (!win.remplib.tracker) {
            var fn, i, funcs = "init trackEvent trackPageview trackCommerce".split(" ");
            win.remplib.tracker = {_: []};
            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib.tracker[fn] = mock(fn);
            }
        }
@isset($trackerUrl)
        load("{{ $trackerLibUrl }}");
@endisset
        load("{{ $campaignLibUrl }}");
    })(window, document);

    var rempConfig = {
        // required if you're using REMP segments
        token: "UUIDv4",
        // optional
        userId: "any-string",
@isset($trackerUrl)
        // required if you want to automatically track banner events to BEAM Tracker
        tracker: {
            url: "{{ $trackerUrl }}",
            article: {
                id: "13579"
            }
        },
@endisset
        // required
        campaign: {
            url: "{{ $campaignUrl }}",
        }
};
@isset($trackerUrl)
remplib.campaign.init(rempConfig);
@endisset
remplib.tracker.init(rempConfig);
</script>