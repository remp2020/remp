@extends('layouts.app')

@push('scripts')
<script type="text/javascript">
    var rempCampaign = {
        "userId": "92363"
    };
    (function () {
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.async = true;
        s.src = 'http://rempcampaign.local/assets/js/lib.js';
        var p = document.getElementsByTagName('script')[0];
        p.parentNode.insertBefore(s, p);
    })();
</script>
@endpush

@section('content')
@endsection