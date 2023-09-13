<div class="pmb-block">
    <div class="pmbb-header">
        <h2><i class="zmdi zmdi-male-female m-r-5"></i> Gender balance - first photo</h2>
    </div>
    @if (is_null($menCount) || is_null($womenCount))
        No data available.
    @elseif(is_null($womenPercentage))
        No people identified.
    @else
        <div class="pmbb-body p-l-30">
            <div class="pmbb-view">
                <dl class="dl-horizontal">
                    <dt>Men count</dt>
                    <dd>{{$menCount}}</dd>
                </dl>

                <dl class="dl-horizontal">
                    <dt>Women count</dt>
                    <dd>{{$womenCount}}</dd>
                </dl>

                <dl class="dl-horizontal">
                    <dt>Women representation</dt>
                    <dd>{{$womenPercentage}}%</dd>
                </dl>
            </div>
        </div>
    @endif
</div>
