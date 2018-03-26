{!! Form::token() !!}

<div class="form-group fg-float m-b-30">
    <div class="fg-line">
        {!! Form::text('name', null, ['class' => 'form-control fg-input']) !!}
        {!! Form::label('name', null, ['class' => 'fg-label']) !!}
    </div>
</div>

{!! Form::button('<i class="zmdi zmdi-check"></i> Save', [
    'class' => 'btn btn-info waves-effect',
    'type' => 'submit',
    'name' => 'action',
    'value' => 'save'
]) !!}

{!! Form::button('<i class="zmdi zmdi-mail-send"></i> Save and close', [
    'class' => 'btn btn-info waves-effect',
    'type' => 'submit',
    'name' => 'action',
    'value' => 'save_close'
]) !!}