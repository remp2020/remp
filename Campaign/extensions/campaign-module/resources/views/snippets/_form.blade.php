<div id="snippet-form">
    <snippet-form></snippet-form>
</div>

@push('scripts')
    <script type="text/javascript">
        var snippet = {
            "name": '{!! $snippet->name !!}' || null,
            "value": {!! json_encode($snippet->value) !!} || null,
            "validateUrl": {!! @json(route('snippets.validateForm', ['snippet' => $snippet])) !!},
        }

        remplib.snippetForm.bind("#snippet-form", snippet);
    </script>

@endpush
