@push('head')
<link href="/assets/css/prism/prism-vs.css" rel="stylesheet">
<script src="/assets/vendor/prism/prism.js"></script>

<script src="/assets/vendor/sugar/release/sugar-full.min.js"></script>
<script src="/assets/js/jquerymy.min.js"></script>
<link rel="stylesheet" href="/assets/css/banner.css" />
<script src="/assets/js/banner.js"></script>

<style>
    #preview-box {
        position: relative;
    }
</style>
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
            <div class="row top10">
                <div class="col-md-2"><strong>Snippet</strong></div>
                <div class="col-md-10">
                    <pre class="language-html"><code class="language-html">{{ '<script type="text/javascript">console.log("snippet working");</script>' }}</code></pre>
                </div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Position</strong></div>
                <div class="col-md-10">{{ $positions[$banner->position]->label }}</div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Dimensions</strong></div>
                <div class="col-md-10">{{ $dimensions[$banner->dimensions]->label }}</div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Alignment</strong></div>
                <div class="col-md-10">{{ $alignments[$banner->text_align]->label }}</div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Preview</strong></div>
                <div class="col-md-10">
                    <div id="preview-box" class="preview-box">
                        <p id="preview-text" class="preview-text"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var positions = {!! json_encode($positions) !!};
        var dimensions = {!! json_encode($dimensions) !!};
        var alignments = {!! json_encode($alignments) !!};

        Campaign.banner.init(positions, dimensions, alignments);
        Campaign.banner.dataFromBanner({!! json_encode($banner) !!});
        Campaign.banner.show($('#preview-box'), $('#preview-text'));
    </script>

@endsection