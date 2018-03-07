<div id="campaigns-list-item-{{ $id }}">
    <toggle
        name="campaigns-list-item-active-toggle-{{ $id }}"
        id="campaigns-list-item-active-toggle-{{ $id }}"
        :is-checked="{!! @json($active) !!}"

        method="patch"
        toggle-url="{!! route('api.campaigns.toggle_active', ['campaign' => $id]) !!}"
        auth-token="{{ config('services.remp.sso.api_token') }}"
        :callback="callback"
    ></toggle>  
</div>

<script type="text/javascript">
    new Vue({
        el: "#campaigns-list-item-{{ $id }}",
        components: {
            Toggle
        },
        methods: {
            callback: function (response, status) {
                if (response.status !== 200) {
                    $.notify({
                        message: 'Can\'t de/activate campaign.' 
                    }, {
                        allow_dismiss: false,
                        type: 'danger'
                    });
                }
            }
        }
    });
</script>

