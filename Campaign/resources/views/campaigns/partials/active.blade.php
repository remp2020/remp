<div id="campaigns-list-item-{{ $id }}">
    <toggle></toggle>  
</div>

<script type="text/javascript">
    var toggle = {
        name: "campaigns-list-item-{{ $id }}",
        id: "campaigns-list-item-{{ $id }}",
        isChecked: @if($active) true @else false @endif,
        disabled: false,

        method: 'patch',
        activateUrl: "{!! route('campaigns.api.toggle_active', ['campaign' => $id]) !!}",
        deactivateUrl: "{!! route('campaigns.api.toggle_active', ['campaign' => $id]) !!}",
        authToken: "{{ config('services.remp.sso.api_token') }}"
    };

    remplib.campaignActiveToggle.bind("#campaigns-list-item-{{ $id }}", toggle);
</script>

