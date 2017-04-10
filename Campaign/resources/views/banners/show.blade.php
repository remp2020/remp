@push('head')
<link href="/assets/vendor/prism/themes/prism-coy.css" rel="stylesheet">
<script src="/assets/vendor/prism/prism.js"></script>
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
                <div class="col-md-2"><strong>Width</strong></div>
                <div class="col-md-10">{{ $banner->width }}px</div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Height</strong></div>
                <div class="col-md-10">{{ $banner->height }}px</div>
            </div>
            <div class="row top10">
                <div class="col-md-2"><strong>Preview</strong></div>
                <div class="col-md-10">{!! HTML::image($banner->storage_uri, 'banner preview', [
                    'style' => 'max-height: 200px'
                ]) !!}</div>
            </div>
        </div>
    </div>

@endsection