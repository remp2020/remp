<div id="entity-form">
    <entity-form></entity-form>
</div>

@push('scripts')

<script type="text/javascript">
    let entity = {
        "parent_id": '{!! $entity->parent_id !!}' || null,
        "name": '{!! $entity->name !!}',
        "params": {!! @json($entity->params) !!},
        "types": {!! @json(\Remp\BeamModule\Model\EntityParam::getAllTypes()) !!},
        "rootEntities": {!! @json($rootEntities) !!} || null,
        "validateUrl": {!! @json(route('entities.validateForm', ['entity' => $entity])) !!}
    };

    remplib.entityForm.bind("#entity-form", entity);
</script>

@endpush
