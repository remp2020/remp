<div id="segment-form">
    <segment-form></segment-form>
</div>

@push('scripts')

<script type="text/javascript">
    let segment = {
        "name": '{!! $segment->name !!}' || null,
        "code": '{!! $segment->code !!}' || null,
        "active": {!! @json($segment->active) !!} || null,
        "rules": {!! $segment->rules->toJson() !!},
        "removedRules": [],
        "eventCategories": ["campaign"],
        "eventNames": {
            "campaign": ["display", "click", "close"]
        }
    }
    remplib.segmentForm.bind("#segment-form", segment);
</script>

@endpush