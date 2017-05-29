@push('head')
<link href="/assets/css/prism/prism-vs.css" rel="stylesheet">
<script src="/assets/vendor/prism/prism.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/highlight.min.js"></script>

<style type="text/css">
    pre {
        padding: 0;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript">
    hljs.initHighlightingOnLoad();
</script>
@endpush

@extends('layouts.app')

@section('title', 'Show campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns: {{ $campaign->name }}</h2>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h2>JS snippet</h2>
            </div>
            <div class="card-body card-padding">
                <div class="row">
                    <div class="col-md-12">
                        @php
                            $libUrl = asset("assets/js/lib.js");
                            $baseUrl = url('/');
                            $snippet = <<<HTML
<script type="text/javascript">
    var rempCampaign = {
        "server": "{$baseUrl}",
        "userId": "92363"
    };
    (function () {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = '{$libUrl}';
        var p = document.getElementsByTagName('script')[0];
        p.parentNode.insertBefore(s, p);
    })();
</script>
HTML;
                        @endphp
                        <pre><code class="html">{{ $snippet }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2>Show campaign <small>{{ $campaign->name }}</small></h2>
            </div>
            <div class="card-body card-padding">
                <div class="row m-t-10 cp-container">
                    <div class="col-md-4"><strong>Banner</strong></div>
                    <div class="col-md-8">{{ $campaign->banner->name }}</div>
                </div>
                <div class="row m-t-10 cp-container">
                    <div class="col-md-4"><strong>Segment</strong></div>
                    <div class="col-md-8">{{ $campaign->segment_id }}</div>
                </div>
                <div class="row m-t-10 cp-container">
                    <div class="col-md-4"><strong>Active</strong></div>
                    <div class="col-md-8">{{ @yesno($campaign->active) }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
