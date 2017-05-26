@push('head')
<link href="/assets/css/prism/prism-vs.css" rel="stylesheet">
<script src="/assets/vendor/prism/prism.js"></script>

<link href="/assets/vendor/farbtastic/farbtastic.css" rel="stylesheet">
<script src="/assets/js/banner.js"></script>

<style>
    #preview-box {
        position: relative;
    }
    i.color {
        width: 20px;
        height: 20px;
        border-radius: 2px;
        border: 1px solid black;
        position: absolute;
        left: -15px;
    }
</style>
@endpush


@push('scripts')

<script type="text/javascript">
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var banner = Campaign.banner.fromModel({!! $banner->toJson() !!});

    banner.show = true;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    Campaign.banner.bindPreview(banner);
    new Vue({
        el: '#banner-preview'
    });
</script>

@endpush

@extends('layouts.app')

@section('title', 'Show banner')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Show banner <small>{{ $banner->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            <div class="row m-t-10 cp-container">
                <div class="col-md-2"><strong>Text color</strong></div>
                <div class="col-md-10"><i class="color" style="background-color: {{ $banner->text_color }}"></i> {{ $banner->text_color }}</div>
            </div>
            <div class="row m-t-10 cp-container">
                <div class="col-md-2"><strong>Font size</strong></div>
                <div class="col-md-10">{{ $banner->font_size }}</div>
            </div>
            <div class="row m-t-10 cp-container">
                <div class="col-md-2"><strong>Background color</strong></div>
                <div class="col-md-10"><i class="color" style="background-color: {{ $banner->background_color }}"></i> {{ $banner->background_color }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Position</strong></div>
                <div class="col-md-10">{{ $positions[$banner->position]->name }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Dimensions</strong></div>
                <div class="col-md-10">{{ $dimensions[$banner->dimensions]->name }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Alignment</strong></div>
                <div class="col-md-10">{{ $alignments[$banner->text_align]->name }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Transition</strong></div>
                <div class="col-md-10">{{ $banner->transition }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Target URL</strong></div>
                <div class="col-md-10">{{ $banner->target_url }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Display delay</strong></div>
                <div class="col-md-10">{{ $banner->display_delay }} ms</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Close timeout</strong></div>
                <div class="col-md-10">{{ $banner->close_timeout }} ms</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>Closeable</strong></div>
                <div class="col-md-10">{{ @yesno($banner->closeable) }}</div>
            </div>
            <div class="row m-t-10">
                <div class="col-md-2"><strong>JS snippet</strong></div>
                <div class="col-md-10">
                    @php
                    $url = route('banners.preview', $banner->uuid);
                    $snippet = <<<JS
<script type="text/javascript">
    (function () {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = '{$url}';
        var p = document.getElementsByTagName('script')[0];
        p.parentNode.insertBefore(s, p);
    })();
</script>

JS;
                    @endphp
                    <pre class="language-html"><code class="language-html">{{ $snippet }}</code></pre>
                </div>
            </div>
            <div class="row m-t-10" style="min-height: 250px;">
                <div class="col-md-2"><strong>Preview</strong></div>
                <div class="col-md-10">
                    <div id="banner-preview">
                        <banner-preview></banner-preview>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
