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
@isset($trackerUrl)
        if (!win.remplib.tracker) {
            var fn, i, funcs = "init trackEvent trackPageview trackCommerce".split(" ");
            win.remplib.tracker = {_: []};
            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib.tracker[fn] = mock(fn);
            }
        }
        load("{{ $trackerLibUrl }}");
@endisset
        load("{{ $campaignLibUrl }}");
    })(window, document);

    var rempConfig = {
        // required if you're using REMP segments
        token: String, // UUIDv4 based REMP Beam token
        // optional
        userId: String,
@isset($trackerUrl)
        // required if you want to automatically track banner events to BEAM Tracker
        tracker: {
            url: "{{ $trackerUrl }}",
            article: {
                id: String
            }
        },
@endisset
        // required
        campaign: {
            url: "{{ $campaignUrl }}",
            variables: {
                // variables replace template placeholders in banners,
                // e.g. @{{ email }} -> foo@example.com
                //
                // the callback doesn't pass any parameters, it's required for convenience and just-in-time evaluation
                //
                // missing variable is translated to empty string
                email: {
                    value: function() {
                        return "foo@example.com"
                    }
                }
            }
        }
    };
@isset($trackerUrl)
    remplib.tracker.init(rempConfig);
@endisset
    remplib.campaign.init(rempConfig);
</script>