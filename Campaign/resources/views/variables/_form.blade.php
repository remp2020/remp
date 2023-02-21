<div id="variable-form">
    <variable-form></variable-form>
</div>

@push('scripts')
    <script type="text/javascript">
        var variable = {
            "name": '{!! $variable->name !!}' || null,
            "value": `{{ $variable->value }}` || null,
            "validateUrl": {!! @json(route('variables.validateForm', ['variable' => $variable])) !!},
        }

        remplib.variableForm.bind("#variable-form", variable);
    </script>

@endpush
