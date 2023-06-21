<div class="well">
    <div class="row">
        <div class="col-md-4">
            <h4>Filter by visit date</h4>
            <div id="smart-range-selector">
                {!! Form::hidden('visited_from', $visitedFrom) !!}
                {!! Form::hidden('visited_to', $visitedTo) !!}
                <smart-range-selector from="{{$visitedFrom}}" to="{{$visitedTo}}" :callback="callback">
                </smart-range-selector>
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

<script type="text/javascript">
    new Vue({
        el: "#smart-range-selector",
        components: {
            SmartRangeSelector
        },
        methods: {
            callback: function (from, to) {
                $('[name="visited_from"]').val(from);
                $('[name="visited_to"]').val(to).trigger("change");
            }
        }
    });
</script>