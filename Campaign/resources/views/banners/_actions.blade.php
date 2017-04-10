{{ link_to_route('banners.show', '', [
    'id' => $banner->id
], [
    'class' => 'btn btn-xs palette-Cyan bg waves-effect zmdi zmdi-palette-Cyan zmdi-eye',
]) }}

{{ link_to_route('banners.edit', '', [
    'id' => $banner->id
], [
    'class' => 'btn btn-xs palette-Cyan bg waves-effect zmdi zmdi-palette-Cyan zmdi-edit',
]) }}
