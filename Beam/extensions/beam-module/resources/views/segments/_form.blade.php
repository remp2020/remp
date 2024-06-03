<div id="segment-form">
    <segment-form></segment-form>
</div>

@push('scripts')

<script type="text/javascript">
    let segment = {{ Illuminate\Support\Js::from([
        "name" => $segment->name,
        "code" => $segment->code,
        "active" => $segment->active,
        "rules" => $segment->rules,
        "removedRules" => $segment->removedRules,
        "eventCategories" => $categories,
        "eventActions" => [],
    ]) }};

    remplib.segmentForm.bind("#segment-form", segment);
</script>

@endpush
