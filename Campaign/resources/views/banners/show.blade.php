@push('head')

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
    banner.closeable = false;
    banner.forcedPosition = 'absolute';
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    remplib.banner.bindPreview('#banner-preview', banner);
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
                <div class="actions">
                    <a href="{{ route('banners.edit', $banner) }}" class="btn palette-Cyan bg waves-effect">Edit banner</a>
                </div>
            </div>
            <div class="card-body card-padding">
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if(isset($banner->htmlTemplate))
        <div class="card">
            <div class="card-header">
                <h2>HTML Template</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Text color: </strong>{{ $banner->htmlTemplate->text_color }}
                        <i class="color" style="background-color: {{ $banner->htmlTemplate->text_color }}"></i>
                    </li>
                    <li class="list-group-item">
                        <strong>Font size: </strong>{{ $banner->htmlTemplate->font_size }}
                    </li>
                    <li class="list-group-item">
                        <strong>Background color: </strong>{{ $banner->htmlTemplate->background_color }}
                        <i class="color" style="background-color: {{ $banner->htmlTemplate->background_color }}"></i>
                    </li>
                    <li class="list-group-item">
                        <strong>Dimensions: </strong>{{ $dimensions[$banner->htmlTemplate->dimensions]->name }}
                    </li>
                    <li class="list-group-item">
                        <strong>Alignment: </strong>{{ $alignments[$banner->htmlTemplate->text_align]->name }}
                    </li>
                </ul>
            </div>
        </div>
        @endif

        @if(isset($banner->mediumRectangleTemplate))
            <div class="card">
                <div class="card-header">
                    <h2>Medium Rectangle Template</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>Header text: </strong>{{ $banner->mediumRectangleTemplate->header_text }}
                        </li>
                        <li class="list-group-item">
                            <strong>Main text: </strong>{{ $banner->mediumRectangleTemplate->main_text }}
                        </li>
                        <li class="list-group-item">
                            <strong>Button text: </strong>{{ $banner->mediumRectangleTemplate->button_text }}
                        </li>
                        <li class="list-group-item">
                            <strong>Text color: </strong>{{ $banner->mediumRectangleTemplate->text_color }}
                            <i class="color" style="background-color: {{ $banner->mediumRectangleTemplate->text_color }}"></i>
                        </li>
                        <li class="list-group-item">
                            <strong>Background color: </strong>{{ $banner->mediumRectangleTemplate->background_color }}
                            <i class="color" style="background-color: {{ $banner->mediumRectangleTemplate->background_color }}"></i>
                        </li>
                        <li class="list-group-item">
                            <strong>Button text color: </strong>{{ $banner->mediumRectangleTemplate->button_text_color }}
                            <i class="color" style="background-color: {{ $banner->mediumRectangleTemplate->button_text_color }}"></i>
                        </li>
                        <li class="list-group-item">
                            <strong>Button background color: </strong>{{ $banner->mediumRectangleTemplate->button_background_color }}
                            <i class="color" style="background-color: {{ $banner->mediumRectangleTemplate->button_background_color }}"></i>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h2>Settings</h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Position: </strong>{{ $positions[$banner->position]->name }}
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
                <div class="row cp-container" style="min-height: 300px">
                    <div class="col-md-12">
                        <div id="banner-preview">
                            <banner-preview></banner-preview>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
