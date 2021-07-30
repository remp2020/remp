<div class="row">
    <div class="col-md-6 form-group">
        <div class="form-group">
            <div class="dtp-container fg-line">
                {!! Form::label('name', 'Name', ['class' => 'fg-label']) !!}
                {!! Form::text('name', $variable->name, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            <div class="dtp-container fg-line">
                {!! Form::label('value', 'Value', ['class' => 'fg-label']) !!}
                {!! Form::textarea('value', $variable->value, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
                <button class="btn btn-info waves-effect" name="action" value="save" type="submit"><i class="zmdi zmdi-mail-send"></i> Save</button>
                <button class="btn btn-info waves-effect" name="action" value="save_close" type="submit"><i class="zmdi zmdi-mail-send"></i> Save and close</button>
            </div>
        </div>
    </div>
</div>