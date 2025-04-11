<div class="form-group fg-float m-b-30">
    <div class="fg-line">
        {{ html()->text('name')->attribute('class', 'form-control fg-input') }}
        {{ html()->label('name')->attribute('class', 'fg-label') }}
    </div>
</div>

{{ html()->button('<i class="zmdi zmdi-check"></i> Save', 'submit', 'action')->attributes([
    'class' => 'btn btn-info waves-effect',
    'type' => 'submit',
    'name' => 'action',
    'value' => 'save'
]) }}

{{ html()->button('<i class="zmdi zmdi-mail-send"></i> Save and close', 'submit', 'action')->attributes([
    'class' => 'btn btn-info waves-effect',
    'value' => 'save_close'
]) }}
