{!! Form::token() !!}

<div class="row">
    <div class="col-md-6 form-group">
        <div class="input-group fg-float m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-label"></i></span>
            <div class="fg-line">
                {!! Form::label('Name', null, ['class' => 'fg-label']) !!}
                {!! Form::text('name', $newsletter->name, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Segment', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'segment',
                       $segments,
                       $newsletter->segment,
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
                       $newsletter->mailer_generator_id,
                       ['class' => 'selectpicker',
                       'data-live-search' => 'true',
                       'placeholder' => 'Please select...']
                   ) !!}
                </div>
            </div>
        </div>

        <div class="input-group m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-settings"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Mail Type', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'mail_type_code',
                       $mailTypes,
                       $newsletter->mail_type_code,
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
                       $newsletter->criteria,
                       ['class' => 'selectpicker',
                       'placeholder' => 'Please select...']
                   ) !!}
                </div>
            </div>
        </div>

        <div class="input-group m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-time-interval"></i></span>
            <div class="fg-line">
                <label class="fg-label">Timespan of data in days</label>
                {!! Form::number('timespan',
                $newsletter->timespan,
                ['class' => 'form-control fg-input', 'min' => 1, 'placeholder' => "How old data (conversions, pageviews, ...) is included", 'required' => 'required']) !!}
            </div>
        </div>

        <div class="input-group m-t-15">
            <span class="input-group-addon"><i class="zmdi zmdi-file-text"></i></span>
            <div class="fg-line">
                {!! Form::label('How many articles', null, ['class' => 'fg-label']) !!}
                {!! Form::number('articles_count', $newsletter->articles_count, ['class' => 'form-control fg-input', 'min' => 1, 'max' => 100]) !!}
            </div>
        </div>

        <h5 class="m-t-30">Email parameters</h5>

        <div class="input-group m-t-20">
            <span class="input-group-addon"><i class="zmdi zmdi-email"></i></span>
            <div class="fg-line">
                {!! Form::label('Email subject', null, ['class' => 'fg-label']) !!}
                {!! Form::text('email_subject', $newsletter->email_subject, ['class' => 'form-control fg-input', 'placeholder' => 'e.g. "Top 10 articles this week"']) !!}
            </div>
        </div>

        <div class="input-group m-t-10">
            <span class="input-group-addon"><i class="zmdi zmdi-arrow-right"></i></span>
            <div class="fg-line">
                {!! Form::label('Email from', null, ['class' => 'fg-label']) !!}
                {!! Form::text('email_from', $newsletter->email_from, ['class' => 'form-control fg-input', 'placeholder' => 'e.g. REMP <info@remp2020.com>']) !!}
            </div>
        </div>
    </div>
</div>

<div id="recurrence-selector" class="row">
    <div class="col-md-6 form-group">
        <h5>Start date and recurrence</h5>
        <div class="m-t-20">
            {!! Form::hidden('starts_at', $newsletter->starts_at) !!}
            {!! Form::hidden('recurrence_rule', $newsletter->recurrence_rule) !!}
            @php
                $recurrence = old('recurrence_rule', $newsletter->recurrence_rule);
                $recurrence = $recurrence !== null ? "'{$recurrence}'" : 'null';
            @endphp
            <recurrence-selector
                    start-date="{{ old('starts_at', $newsletter->starts_at) }}"
                    :recurrence="{{ $recurrence }}"
                    :callback="callback">
            </recurrence-selector>
        </div>
    </div>

    <div class="col-md-6">
        <h5 v-show="rrule" style="margin-top: 14px">Next few recurrences (up to 10):</h5>
        <rule-ocurrences :rrule='rrule'></rule-ocurrences>
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
            RecurrenceSelector, RuleOcurrences
        },
        data: function() {
            return {
                rrule: null,
                submitAction: null
            }
        },
        methods: {
            callback: function (startsAt, recurrenceRule) {
                this.rrule = recurrenceRule
                $('[name="starts_at"]').val(formatDateUtc(new Date(startsAt)));
                $('[name="recurrence_rule"]').val(recurrenceRule);
            }
        }
    });
</script>