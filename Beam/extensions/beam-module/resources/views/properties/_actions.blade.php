{{ html()->a(
    href: route('accounts.properties.edit', ['account' => $account, 'property' => $property]),
    contents: ''
)->attribute('class', 'btn btn-xs palette-Cyan bg waves-effect zmdi zmdi-palette-Cyan zmdi-edit') }}
