<div id="campaigns-list-item-{{ $id }}">
    <toggle
        name="campaigns-list-item-active-toggle-{{ $id }}"
        id="campaigns-list-item-active-toggle-{{ $id }}"
        :is-checked="{!! @json($active) !!}"

        method="post"
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
                        message: 'Cannot toggle campaign active status' 
                    }, {
                        allow_dismiss: false,
                        type: 'danger'
                    });
                }
                // dispatch event used by schedules to reload datatable
                document.dispatchEvent(new Event('campaign_active_toggled'));
            }
        }
    });
</script>
