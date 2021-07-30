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
    var variables = {!! json_encode($variables) !!};

    var banner = remplib.banner.fromModel({!! $banner->toJson() !!});
    banner.show = true;
    banner.alignmentOptions = alignments;
    banner.dimensionOptions = dimensions;
    banner.positionOptions = positions;
    banner.forcedPosition = 'absolute';
    banner.variables = variables;
    banner.validateUrl = {!! @json(route('banners.validateForm')) !!};
    banner.clientSiteUrl = '{{ Config::get('app.client_site_url') }}';

    remplib.bannerForm.bind("#banner-form", banner);
</script>

@endpush