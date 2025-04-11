<div class="row">
    <div class="col-md-6 form-group">
        <div class="input-group fg-float m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
            <div class="fg-line">
                {{ html()->label('Name')->attribute('class', 'fg-label') }}
                {{ html()->text('name')->attribute('class', 'form-control fg-input') }}
            </div>
        </div>

        <div class="input-group fg-float m-t-30 checkbox">
            <label class="m-l-15">
                Activate
                {{ html()->hidden('active', 0) }}
                {{ html()->checkbox('active') }}
                <i class="input-helper"></i>
            </label>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
                {{ html()->button('<i class="zmdi zmdi-check"></i> Save', 'submit', 'action')->attributes([
                    'class' => 'btn btn-info waves-effect',
                    'value' => 'save'
                ]) }}

                {{ html()->button('<i class="zmdi zmdi-mail-send"></i> Save and close', 'submit', 'action')->attributes([
                    'class' => 'btn btn-info waves-effect',
                    'value' => 'save_close'
                ]) }}
            </div>
        </div>
    </div>
</div>
