@extends('layouts.app')

@section('title', 'Show campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns: {{ $campaign->name }}</h2>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h2>Settings</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Banner: </strong>{{ link_to_route('banners.show', $campaign->banner->name, $campaign->banner) }}
                    </li>
                    <li class="list-group-item">
                        <strong>Segments: </strong>
                        <ul>
                        @foreach($campaign->segments as $segment)
                            <li>{{ $segment->code }}</li>
                        @endforeach
                        </ul>
                    </li>
                    <li class="list-group-item">
                        <strong>Active: </strong>{{ @yesno($campaign->active) }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>JS snippet</h2>
            </div>
            <div class="card-body card-padding">
                <div class="row">
                    <div class="col-md-12">
                        @php
                            $libUrl = asset("/assets/vendor/js/remplib.js");
                            $targetUrl = url('/');
                            $snippet = <<<HTML
<script type="text/javascript">
    (function(win, doc) {
        function mock(fn) {
            return function() {
                this._.push([fn, arguments])
            }
        }
        if (!win.remplib) {
            var fn, i, funcs = "init identify".split(" "),
                script = doc.createElement("script");
            win.remplib = {_: []};

            for (i = 0; i < funcs.length; i++) {
                fn = funcs[i];
                win.remplib[fn] = mock(fn);
            }

            script.type = "text/javascript";
            script.async = true;
            script.src = "{$libUrl}";
            doc.getElementsByTagName("head")[0].appendChild(script);
        }
    })(window, document);

    remplib.init({
        "target": "{$targetUrl}",
        "token": "beam-property-token"
    });
    remplib.identify("user-identifier"); // optional
</script>
HTML;
                        @endphp
                        <pre><code class="html">{{ $snippet }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
