<div id="entity-form">
    <entity-form></entity-form>
</div>

@push('scripts')

<script type="text/javascript">
    let entity = {
        "parent_id": '{!! $entity->parent_id !!}' || null,
        "name": '{!! $entity->name !!}',
        "schema": @if($entity->schema){!! $entity->schema !!} @else { "type": "object", "required": [], "properties": {} }@endif,
        "entities": {!! $entities !!} || null,
        "validateUrl": {!! @json(route('entities.validateForm', ['entity' => $entity])) !!}
    };

    remplib.entityForm.bind("#entity-form", entity);
</script>

@endpush