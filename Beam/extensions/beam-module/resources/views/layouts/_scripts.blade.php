<script type="application/javascript">
    $(document).ready(function() {
        let delay = 250;
        @foreach ($errors->all() as $error)
        (function(delay) {
            window.setTimeout(function() {
                $.notify({
                    message: '{{ $error }}'
                }, {
                    allow_dismiss: false,
                    type: 'danger'
                });
            }, delay);
        })(delay);
        delay += 250;
        @endforeach
        @if (session('warning'))
        $.notify({
            message: '{{ session('warning') }}'
        }, {
            allow_dismiss: false,
            type: 'warning',
            placement: {
                from: "bottom",
                align: "left"
            }
        });
        @endif
        @if (session('success'))
        $.notify({
            message: '{{ session('success') }}'
        }, {
            allow_dismiss: false,
            type: 'info',
            placement: {
                from: "bottom",
                align: "left"
            }
        });
        @endif
    });
</script>