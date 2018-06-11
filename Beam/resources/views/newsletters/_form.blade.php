{!! Form::token() !!}

<div class="row">
    <div class="col-md-6 form-group">
        <div class="input-group fg-float m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-label"></i></span>
            <div class="fg-line">
                {!! Form::text('name', null, ['class' => 'form-control fg-input']) !!}
                {!! Form::label('Name', null, ['class' => 'fg-label']) !!}
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Segment', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'segment_code',
                       $segments,
                       null,
                       [
                        'class' => 'selectpicker',
                       'data-live-search' => 'true',
                       'placeholder' => 'Please select...'
                       ]
                   ) !!}
                </div>
            </div>
        </div>

        <div class="input-group m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-settings"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Generator', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'mailer_generator_id',
                       $generators,
                       null,
                       ['class' => 'selectpicker',
                       'data-live-search' => 'true',
                       'placeholder' => 'Please select...']
                   ) !!}
                </div>
            </div>
        </div>

        <div class="input-group m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-key"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Criteria', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'criteria',
                       $criteria,
                       null,
                       ['class' => 'selectpicker',
                       'placeholder' => 'Please select...']
                   ) !!}
                </div>
            </div>
        </div>

        <div class="input-group fg-float m-t-15">
            <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
            <div class="fg-line">
                <label for="articles_count" class="fg-label">How many articles</label>
                <input class="form-control fg-input" name="articles_count" id="articles_count" type="number" value="1" min="1">
            </div>
        </div>
    </div>
</div>

<div id="recurrence-selector" class="row">
    <div class="col-md-6 form-group">
        <h5>Start date and recurrence</h5>

        <div class="m-t-20">
            {!! Form::hidden('starts_at') !!}
            {!! Form::hidden('reccurrence_rule') !!}
            <recurrence-selector start-date="{{$startsAt}}" recurrence="{{$recurrenceRule}}" :callback="callback">
            </recurrence-selector>
        </div>

    </div>

    <div class="col-md-6">
        <h5 style="margin-top: 44px">Next few recurrences (up to 10):</h5>
        <rule-occurrences :rrule='rrule'></rule-occurrences>
    </div>

    <div class="col-md-12">
        <div class="input-group m-t-10">
            <div class="fg-line">
                <input type="hidden" name="action" :value="submitAction">

                <button class="btn btn-info waves-effect" type="submit" name="action" value="save">
                    <i class="zmdi zmdi-check"></i> Save
                </button>
                <button class="btn btn-info waves-effect" type="submit" name="action" value="save_close">
                    <i class="zmdi zmdi-mail-send"></i> Save and close
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    function formatDateUtc(d) {
        return d.getUTCFullYear() + "-" + ("0"+(d.getUTCMonth()+1)).slice(-2) + "-" + ("0"+d.getUTCDate()).slice(-2) +
             " " + ("0" + d.getUTCHours()).slice(-2) + ":" + ("0" + d.getUTCMinutes()).slice(-2) + ":" + ("0" + d.getUTCSeconds()).slice(-2);
    }

    new Vue({
        el: "#recurrence-selector",
        components: {
            RecurrenceSelector, RuleOccurrences
        },
        data() {
            return {
                rrule: null
            }
        },
        methods: {
            callback: function (startsAt, reccurrenceRule) {
                this.rrule = reccurrenceRule
                $('[name="starts_at"]').val(formatDateUtc(new Date(startsAt)).toUTCString());
                $('[name="reccurrence_rule"]').val(reccurrenceRule);
            }
        }
    });
</script>