<form method="post" action="{{ route('authorSegments.saveConfiguration') }}">

    <form-validator url="{{route('authorSegments.validateConfiguration')}}"></form-validator>

    <div class="col-md-6">
        {{ csrf_field() }}

        <h5>Criteria</h5>

        <p class="c-black f-500 m-b-10">Minimal ratio of (author articles/all articles) read by user:</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_ratio" class="form-control input-sm" value="{{ old('min_ratio', $minRatio) }}" name="min_ratio" required placeholder="e.g. 0.25 (value between 0.0 - 1.0)" type="number" step="0.01" min="0" max="1" />
            </div>
        </div>

        <p class="c-black f-500 m-b-10">Minimal number of author articles read by user:</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_views" class="form-control input-sm" value="{{ old('min_views', $minViews) }}" placeholder="e.g. 5" required name="min_views" min="0" type="number" />
            </div>
        </div>

        <p class="c-black f-500 m-b-10">Minimal average time spent on author's articles by user (seconds):</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_average_timespent" class="form-control input-sm" value="{{ old('min_average_timespent', $minAverageTimespent) }}" required placeholder="e.g. 120 (value in seconds)" name="min_average_timespent" min="0" type="number" />
            </div>
        </div>

        <h5>Other options</h5>

        <p class="c-black f-500 m-b-10">Use data from last (days):</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="days_in_past" class="form-control input-sm" value="{{ old('days_in_past', $daysInPast) }}" name="days_in_past" required placeholder="e.g. 30 (value between 1 and 90)" type="number" step="1" min="1" max="90" />
            </div>
        </div>

        <input class="btn palette-Cyan bg waves-effect" type="submit" value="Save" />
    </div>
</form>