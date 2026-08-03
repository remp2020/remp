<div id="collection-form">
    <collection-form></collection-form>
</div>
@push('scripts')

<script type="text/javascript">
    var collection = {{ Illuminate\Support\Js::from([
        "name" => $collection->name,
        "action" => $action,
        "selectedCampaigns" => $selectedCampaigns,
        "allCampaigns" => $campaigns,
    ]) }};

    remplib.collectionForm.bind("#collection-form", collection);
</script>

@endpush
