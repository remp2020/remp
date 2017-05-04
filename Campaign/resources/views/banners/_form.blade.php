@php
/* @var $dimensions \Illuminate\Support\Collection */
/* @var $positions \Illuminate\Support\Collection */
/* @var $alignments \Illuminate\Support\Collection */
/* @var $banner \App\Banner */

@endphp

@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

<link href="/assets/vendor/farbtastic/farbtastic.css" rel="stylesheet">
<script src="/assets/vendor/farbtastic/farbtastic.js"></script>

<script src="/assets/js/banner.js"></script>

@endpush

<banner-form></banner-form>

@push('scripts')

<script type="text/javascript">
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');

    var bannerData = {
        name: '{{ $banner->name }}',
        dimensions: '{{ $banner->dimensions ? $banner->dimensions : key($dimensions->all()) }}',
        text: '{{ $banner->text }}',
        textAlign: '{{ $banner->text_align ? $banner->text_align : key($alignments->all()) }}',
        fontSize: '{{ $banner->font_size }}',
        targetUrl: '{{ $banner->target_url }}',
        textColor: '{{ $banner->text_color }}',
        backgroundColor: '{{ $banner->background_color }}',
        position: '{{ $banner->position ? $banner->position : key($positions->all()) }}',

        alignmentOptions: alignments,
        dimensionOptions: dimensions,
        positionOptions: positions
    };

    Campaign.banner.init(positions, dimensions, alignments);
    Campaign.banner.bindForm(bannerData);

</script>

@endpush