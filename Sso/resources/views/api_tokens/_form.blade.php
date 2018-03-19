<div class="row">
    <div class="col-md-6 form-group">
        <div class="input-group fg-float m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
            <div class="fg-line">
                {!! Form::label('Name', null, ['class' => 'fg-label']) !!}
                {!! Form::input('text', 'name', null, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group fg-float m-t-30 checkbox">
            <label class="m-l-15">
                Activate
                {!! Form::hidden('active', 0) !!}
                {!! Form::checkbox('active') !!}
                <i class="input-helper"></i>
            </label>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
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
            </div>
        </div>
    </div>
</div>
