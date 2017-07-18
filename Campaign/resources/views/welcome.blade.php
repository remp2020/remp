@extends('layouts.app')

@section('content')
@endsection

@push('scripts')

<script type="text/javascript">
    (function(win, doc) {
        function e(e) {
            return function() {
                var args = arguments;
                if ("initialize" === e && args && args[0].modify && args[0].modify.overlay && "loading" === doc.readyState) {
                    var a = "__inf__overlay__";
                    doc.write('<div id="' + a + '" style="position:absolute;background:#fff;left:0;top:0;width:100%;height:100%;z-index:1010101"></div>');
                    setTimeout(function() {
                        var e = doc.getElementById(a);
                        e && doc.body.removeChild(e);
                    }, args[0].modify.delay || 500)
                }
                this._.push([e, args])
            }
        }
        if (!win.remplib) {
            var fn, i, funcs = "init identify".split(" "),
                script = doc.createElement("script"),
                d = "https:" === doc.location.protocol ? "https:" : "http:";
            win.remplib = {_: []};

            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib[fn] = e(fn);
            }

            script.type = "text/javascript";
            script.async = true;
            script.src = d + "//campaign.remp.app/assets/js/remplib.js";
            doc.getElementsByTagName("head")[0].appendChild(script);
        }
    })(window, document);

    remplib.init({
        "token": "beam-property-token"
    });
    remplib.identify("user-identifier"); // optional
</script>

@endpush