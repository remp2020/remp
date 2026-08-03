<div id="snippet-form">
    <snippet-form></snippet-form>
</div>
@push('scripts')
    <script type="text/javascript">
        var snippet = {{ Illuminate\Support\Js::from([
            "name" => $snippet->name,
            "value" => $snippet->value,
            "validateUrl" => route('snippets.validateForm', ['snippet' => $snippet]),
        ]) }};

        remplib.snippetForm.bind("#snippet-form", snippet);
    </script>

@endpush
