@php
    $beamUrl = url('/');
    $beamLibUrl = asset("/assets/vendor/js/remplib.js");
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
        load("{{ $beamLibUrl }}");
    })(window, document);

    var rempConfig = {
        token: "{{ $property->uuid }}",
        userId: "any-string", // optional
        tracker: {
            url: "{{ $beamUrl }}",
            article: {
                id: "13579"
            }
        },
    };
    remplib.tracker.init(rempConfig);
</script>