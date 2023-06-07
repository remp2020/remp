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
        "removedRules": {!! @json($segment->removedRules) !!},
        "eventCategories": {!! $categories->toJson() !!},
        "eventActions": {},
    }

    remplib.segmentForm.bind("#segment-form", segment);
</script>

@endpush
