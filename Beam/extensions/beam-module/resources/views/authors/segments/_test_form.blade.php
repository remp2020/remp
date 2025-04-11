<form method="post" action="{{ route('authorSegments.compute') }}">

    <form-validator url="{{route('authorSegments.validateTest')}}"></form-validator>

    <div class="col-md-6">
        {{ csrf_field() }}

        <p class="c-black f-500 m-b-10">Minimal ratio of (author articles/all articles) read by user:</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_ratio" class="form-control input-sm" value="{{ old('min_ratio') }}" name="min_ratio" required placeholder="e.g. 0.25 (value between 0.0 - 1.0)" type="number" step="0.01" min="0" max="1" />
            </div>
        </div>

        <p class="c-black f-500 m-b-10">Minimal number of author articles read by user:</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_views" class="form-control input-sm" value="{{ old('min_views') }}" placeholder="e.g. 5" required name="min_views" min="0" type="number" />
            </div>
        </div>

        <p class="c-black f-500 m-b-10">Minimal average time spent on author's articles by user (seconds):</p>

        <div class="form-group">
            <div class="fg-line">
                <input id="min_average_timespent" class="form-control input-sm" value="{{ old('min_average_timespent') }}" required placeholder="e.g. 120 (value in seconds)" name="min_average_timespent" min="0" type="number" />
            </div>
        </div>

        <p class="c-black f-500 m-b-10">Use data from the last:</p>

        <div class="radio m-b-15">
            <label>
                {{ html()->radio('history', true, '30') }}
                <i class="input-helper"></i>
                30 days
            </label>
        </div>

        <div class="radio m-b-15">
            <label>
                {{ html()->radio('history', false, '60') }}
                <i class="input-helper"></i>
                60 days
            </label>
        </div>

        <div class="radio m-b-15">
            <label>
                {{ html()->radio('history', false, '90') }}
                <i class="input-helper"></i>
                90 days
            </label>
        </div>


        <div class="form-group">
            <div class="fg-line">
                <input id="email" class="form-control input-sm" value="{{ old('email') }}" placeholder="Email to send results" name="email" type="text" required />
            </div>
        </div>

        <input class="btn palette-Cyan bg waves-effect" type="submit" value="Compute" />
    </div>
</form>