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

    var banner = remplib.banner.fromModel({!! $banner->toJson() !!});
    banner.show = true;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    remplib.banner.bindPreview(banner);
    remplib.banner.bindForm(banner);

</script>

@endpush