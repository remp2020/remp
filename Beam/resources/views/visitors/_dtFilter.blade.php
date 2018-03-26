<div class="well">
    <div class="row">
        <div class="col-md-3">
            <h4>Filter by visit date</h4>
            <div class="input-group m-b-10">
                <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                <div class="dtp-container fg-line">
                    {!! Form::datetime('visited_from', $visitedFrom, array_filter([
                        'class' => 'form-control date-picker',
                        'placeholder' => 'Visited from...'
                    ])) !!}
                </div>
                <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                <div class="dtp-container fg-line">
                    <div class="dtp-container fg-line">
                        {!! Form::datetime('visited_to', $visitedTo, array_filter([
                            'class' => 'form-control date-picker',
                            'placeholder' => 'Visited to...'
                        ])) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <h4>Filter by subscription status</h4>
            <div class="input-group m-b-10">
                <div class="radio m-b-15">
                    <label>
                        {{ Form::radio('subscriber', '1', true) }}
                        <i class="input-helper"></i> Subscribers

                    </label>
                </div>
                <div class="radio m-b-15">
                    <label>
                        {{ Form::radio('subscriber', '0') }}
                        <i class="input-helper"></i> Non-subscribers
                    </label>
                </div>
                <div class="radio m-b-15">
                    <label>
                        {{ Form::radio('subscriber', '') }}
                        <i class="input-helper"></i> Everyone
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>