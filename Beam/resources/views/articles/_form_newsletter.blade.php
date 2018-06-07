{!! Form::token() !!}

<div class="row">
    <div class="col-md-6 form-group">
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
                       'generator_id',
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
                <input class="form-control fg-input" name="name" id="articles_count" type="number" min="1">
            </div>
        </div>
    </div>
</div>

<div id="recurrence-selector" class="row">
    <div class="col-md-6 form-group">
        <h5>Start date and recurrence</h5>

        <div class="m-t-20">
            {!! Form::hidden('start_date', $startDate) !!}
            {!! Form::hidden('recurrence_string', $recurrenceString) !!}
            <recurrence-selector start-date="{{$startDate}}" recurrence="{{$recurrenceString}}" :callback="callback">
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
                <button class="btn btn-info waves-effect" type="submit"><i class="zmdi zmdi-mail-send"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
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
            callback: function (startDate, recurrenceString) {
                this.rrule = recurrenceString
                $('[name="start_date"]').val(startDate);
                $('[name="recurrence_string"]').val(recurrenceString);
            }
        }
    });
</script>