@extends('layouts.app')

@section('content')
@endsection

@push('scripts')
    <script type="text/javascript">
        (function(win, doc) {
            function mock(fn) {
                return function() {
                    this._.push([fn, arguments])
                }
            }
            if (!win.remplib) {
                var fn, i, funcs = "init identify run".split(" "),
                    script = doc.createElement("script"),
                    d = "https:" === doc.location.protocol ? "https:" : "http:";
                win.remplib = {_: []};

                for (i = 0; i < funcs.length; i++) {
                    fn = funcs[i];
                    win.remplib[fn] = mock(fn);
                }

                script.type = "text/javascript";
                script.async = true;
                script.src = d + "http://campaign.remp.app/assets/js/remplib.js";
                doc.getElementsByTagName("head")[0].appendChild(script);
            }
        })(window, document);

        remplib.init({
            "target": "http://campaign.remp.app",
            "token": "beam-property-token"
        });
        remplib.identify("user-identifier"); // optional
        remplib.run();
    </script>
@endpush