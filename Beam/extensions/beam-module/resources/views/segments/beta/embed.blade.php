<script src="{{ asset('/vendor/beam/iframeResizer/iframeResizer.contentWindow.min.js') }}"></script>

<script type="text/javascript">
    window.Segments = {
        config: {
@if ($segment)
            SEGMENT_ID: {{ $segment->id }},
@endif
            API_HOST: "{{ config('services.remp.beam.segments_addr') }}",
@if (config('services.remp.beam.segmenter.auth_token'))
            AUTH_TOKEN: "{{ config('services.remp.beam.segments_auth_token') }}",
@endif
            CANCEL_PATH: "{{ route('segments.index') }}"
        }
    };
</script>

<div id="app"></div>

<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons" rel=stylesheet>
<link rel="stylesheet" href="{{ asset('/vendor/beam/segmenter/css/chunk-vendors.css') }}">
<link rel="stylesheet" href="{{ asset('/vendor/beam/segmenter/css/app.css') }}">
<script type="text/javascript" src="{{ asset('/vendor/beam/segmenter/js/chunk-vendors.js') }}"></script>
<script type="text/javascript" src="{{ asset('/vendor/beam/segmenter/js/app.js') }}"></script>
