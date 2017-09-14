@php

/* @var $dimensions \Illuminate\Support\Collection */
/* @var $positions \Illuminate\Support\Collection */
/* @var $alignments \Illuminate\Support\Collection */
/* @var $banner \App\Banner */
@endphp

<div id="banner-form">
    <banner-form></banner-form>
</div>

@push('scripts')

<script type="text/javascript">
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var positions = JSON.parse('{!! json_encode($positions) !!}');

    var banner = remplib.banner.fromModel({!! $banner->toJson() !!});
    banner.show = true;
    banner.template = '{{ $template }}';
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;

    remplib.bannerForm.bind("#banner-form", banner);

</script>

@endpush