<div class="row">
    <div class="col-md-6 form-group">
        {!! Form::hidden('campaign_id') !!}

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                <div class="dtp-container fg-line">
                    {!! Form::label('start_time_frontend', 'Start time', ['class' => 'fg-label']) !!}
                    {!! Form::datetime('start_time_frontend', $schedule->start_time, array_filter([
                        'class' => 'form-control date-time-picker',
                        'disabled' => $schedule->id && !$schedule->isEditable() ? 'disabled' : null,
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
                    ])) !!}
                </div>
                {!! Form::hidden('end_time') !!}
            </div>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
                <button class="btn btn-info waves-effect" type="submit"><i class="zmdi zmdi-mail-send"></i> Save and close</button>
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

		$startTimeFE.on('dp.change', function() {
			var st = $(this).data("DateTimePicker").date();
			var et = $endTimeFE.data("DateTimePicker").date();
			if (st && et && st.unix() > et.unix()) {
				$endTimeFE.data("DateTimePicker").date(st);
			}
        });
        $endTimeFE.on("dp.change", function (e) {
        	var st = $startTimeFE.data("DateTimePicker").date();
			var et = $(this).data("DateTimePicker").date();
			if (st && et && et.unix() < st.unix()) {
				$startTimeFE.data("DateTimePicker").date(et);
			}
        }).datetimepicker({useCurrent: false});

        $('form').on('submit', function() {
			var st = $startTimeFE.data("DateTimePicker").date();
			$startTime.val(st ? st.toISOString() : null);
			var et = $endTimeFE.data("DateTimePicker").date();
			$endTime.val(et ? et.toISOString() : null);
			return true;
        })
    })
</script>
