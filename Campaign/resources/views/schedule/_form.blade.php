<div class="row">
    <div class="col-md-6 form-group">
        <div class="input-group">
            <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
            <div class="row">
                <div class="col-md-12 form-group">
                    {!! Form::label('Campaign', null, ['class' => 'fg-label']) !!}
                    {!! Form::select(
                       'campaign_id',
                       $campaigns->mapWithKeys(function (\App\Campaign $campaign) use ($schedule) {
                           return [$campaign->id => $campaign->name];
                       })->toArray(),
                       null,
                       array_filter([
                           'class' => 'selectpicker',
                           'data-live-search' => 'true',
                           'disabled' => $schedule->id ? 'disabled' : null,
                           'placeholder' => 'Please select...'
                       ])
                   ) !!}
                    @isset($schedule->id)
                    {!! Form::hidden('campaign_id') !!}
                    @endisset
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                <div class="dtp-container fg-line">
                    {!! Form::label('start_time_frontend', 'Start time', ['class' => 'fg-label']) !!}
                    {!! Form::datetime('start_time_frontend', $schedule->start_time, array_filter([
                        'class' => 'form-control date-time-picker',
                        'disabled' => !$schedule->isEditable() ? 'disabled' : null,
                    ])) !!}
                </div>
                {!! Form::hidden('start_time') !!}
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-timer-off"></i></span>
                <div class="dtp-container fg-line">
                    {!! Form::label('end_time_frontend', 'End time', ['class' => 'fg-label']) !!}
                    {!! Form::datetime('end_time_frontend', $schedule->end_time, array_filter([
                        'class' => 'form-control date-time-picker',
                        'disabled' => !$schedule->isEditable() ? 'disabled' : null,
                    ])) !!}
                </div>
                {!! Form::hidden('end_time') !!}
            </div>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
                <button class="btn btn-info waves-effect" type="submit"><i class="zmdi zmdi-mail-send"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        var $startTimeFE = $("#start_time_frontend");
        var $startTime = $('input[name="start_time"]');
        var $endTimeFE = $("#end_time_frontend");
        var $endTime = $('input[name="end_time"]');
        $startTimeFE.on("dp.hide", function (e) {
            $endTimeFE.data("DateTimePicker").minDate(e.date);
            $endTimeFE.data("DateTimePicker").defaultDate(e.date);
            $startTime.val(e.date.toISOString());
        });
        $endTimeFE.on("dp.hide", function (e) {
            $startTimeFE.data("DateTimePicker").maxDate(e.date);
            $endTime.val(e.date.toISOString());
        }).datetimepicker({useCurrent: false});
    })
</script>