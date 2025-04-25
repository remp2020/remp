<div class="row">
    <div class="col-md-6 form-group">
        {{ html()->hidden('campaign_id') }}

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-timer"></i></span>
                <div class="dtp-container fg-line">
                    {{ html()->label('Start time', 'start_time_frontend')->attribute('fg-label') }}
                    {{ html()->text('start_time_frontend', $schedule->start_time)->attributes([
                        'class' => 'form-control date-time-picker',
                        'disabled' => $schedule->id && !$schedule->isEditable() ? 'disabled' : null,
                    ]) }}
                </div>
                {{ html()->hidden('start_time') }}
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon"><i class="zmdi zmdi-timer-off"></i></span>
                <div class="dtp-container fg-line">
                    {{ html()->label('End time', 'end_time_frontend')->attribute('fg-label') }}
                    {{ html()->text('end_time_frontend', $schedule->end_time)->attributes([
                        'class' => 'form-control date-time-picker',
                    ]) }}
                </div>
                {{ html()->hidden('end_time') }}
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
