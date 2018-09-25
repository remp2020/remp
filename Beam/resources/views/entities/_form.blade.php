<div id="entity-form">
    <entity-form></entity-form>
</div>

@push('scripts')

<script type="text/javascript">
    let entity = {
        "parent_id": '{!! $entity->parent_id !!}' || null,
        "name": '{!! $entity->name !!}',
        "params": {!! @json(array_values($entity->schema->getParams())) !!},
        "types": {!! @json($entity->schema->getAllTypes()) !!},
        "rootEntities": {!! $rootEntities !!} || null,
        "validateUrl": {!! @json(route('entities.validateForm', ['entity' => $entity])) !!}
    };

    remplib.entityForm.bind("#entity-form", entity);
</script>

@endpush
