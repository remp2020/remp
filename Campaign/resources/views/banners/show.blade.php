@push('head')
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
        right: 15px;
    }
</style>
@endpush


@push('scripts')

<script type="text/javascript">
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var banner = remplib.banner.fromModel({!! $banner->toJson() !!});

    banner.show = true;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    remplib.banner.bindPreview(banner);
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

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show banner <small>{{ $banner->name }}</small></h2>
            </div>
            <div class="card-body card-padding">
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h2>Settings</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Text color: </strong>{{ $banner->text_color }} <i class="color" style="background-color: {{ $banner->text_color }}"></i>
                    </li>
                    <li class="list-group-item">
                        <strong>Font size: </strong>{{ $banner->font_size }}
                    </li>
                    <li class="list-group-item">
                        <strong>Background color: </strong>{{ $banner->background_color }} <i class="color" style="background-color: {{ $banner->background_color }}"></i>
                    </li>
                    <li class="list-group-item">
                        <strong>Position: </strong>{{ $positions[$banner->position]->name }}
                    </li>
                    <li class="list-group-item">
                        <strong>Dimensions: </strong>{{ $dimensions[$banner->dimensions]->name }}
                    </li>
                    <li class="list-group-item">
                        <strong>Alignment: </strong>{{ $alignments[$banner->text_align]->name }}
                    </li>
                    <li class="list-group-item">
                        <strong>Transition: </strong>{{ $banner->transition }}
                    </li>
                    <li class="list-group-item">
                        <strong>Target URL: </strong>{{ $banner->target_url }}
                    </li>
                    <li class="list-group-item">
                        <strong>Display delay: </strong>{{ $banner->display_delay }} ms
                    </li>
                    <li class="list-group-item">
                        <strong>Close timeout: </strong>
                        @if($banner->close_timeout)
                            {{ $banner->close_timeout }} ms
                        @else
                            -
                        @endif
                    </li>
                    <li class="list-group-item">
                        <strong>Closeable: </strong>{{ @yesno($banner->closeable) }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Preview</h2>
            </div>
            <div class="card-body card-padding">
                <div class="row cp-container" style="min-height: {{ $dimensions[$banner->dimensions]->height }};">
                    <div class="col-md-12">
                        <div id="banner-preview">
                            <banner-preview></banner-preview>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h2>Navigate</h2>
            </div>
            <div class="card-body card-padding">
            </div>
        </div>
    </div>

@endsection
