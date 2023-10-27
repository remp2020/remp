<div id="collection-form">
    <collection-form></collection-form>
</div>

@push('scripts')

<script type="text/javascript">
    var collection = {
        "name": '{!! $collection->name !!}' || null,
        "action": '{{ $action }}',
        "selectedCampaigns": {!! @json($selectedCampaigns) !!},
        "allCampaigns": {!! @json($campaigns) !!},
    };

    remplib.collectionForm.bind("#collection-form", collection);
</script>

@endpush
