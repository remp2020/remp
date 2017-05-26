@extends('layouts.app')

@push('scripts')
<script type="text/javascript">
    (function () {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = 'http://rempcampaign.local/banners/preview/05446759-dc4e-4cb9-b3ea-7dd9f5588f96';
        var p = document.getElementsByTagName('script')[0];
        p.parentNode.insertBefore(s, p);
    })();
</script>
@endpush

@section('content')
@endsection