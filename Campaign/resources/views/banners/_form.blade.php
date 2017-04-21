@php
/* @var $dimensions \Illuminate\Support\Collection */
/* @var $positions \Illuminate\Support\Collection */
/* @var $alignments \Illuminate\Support\Collection */
@endphp

@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

<link href="/assets/vendor/farbtastic/farbtastic.css" rel="stylesheet">
<script src="/assets/vendor/farbtastic/farbtastic.js"></script>

<script src="/assets/vendor/sugar/release/sugar-full.min.js"></script>
<script src="/assets/js/jquerymy.min.js"></script>
<script src="/assets/js/banner.js"></script>

<style type="text/css">
    .preview-box {
        color: white;
        position: absolute;
        white-space: pre-line;
        display: table;
        overflow: hidden;
    }
    .preview-text {
        display: table-cell;
        word-break:break-all;
        vertical-align: middle;
        padding: 5px 10px;
    }
    .preview-image {
        opacity: 0.3;
    }
    .cp-value {
        cursor: pointer;
    }
</style>
@endpush


<div class="row">
    <div class="col-md-4">
        <h5>Settings</h5>

        <div class="input-group fg-float m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div class="fg-line">
                {!! Form::label('Name', null, ['class' => 'fg-label']) !!}
                {!! Form::text('name', null, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group fg-float m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div class="fg-line">
                {!! Form::label('HTML text', null, ['class' => 'fg-label']) !!}
                {!! Form::textarea('text', null, ['class' => 'form-control fg-input', 'rows' => 3]) !!}
            </div>
        </div>

        <div class="cp-container">
            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-invert-colors"></i></span>
                <div class="fg-line dropdown">
                    {!! Form::label('Text Color', null, ['class' => 'fg-label']) !!}
                    {!! Form::text('text_color', '#e6e6e6', [
                        'class' => 'form-control cp-value',
                        'data-toggle' => 'dropdown',
                    ]) !!}

                    <div class="dropdown-menu">
                        <div class="color-picker" data-cp-default="#03A9F4"></div>
                    </div>
                    <i class="cp-value"></i>
                </div>
            </div>
        </div>

        <div class="cp-container">
            <div class="input-group fg-float m-t-30">
                <span class="input-group-addon"><i class="zmdi zmdi-invert-colors"></i></span>
                <div class="fg-line dropdown">
                    {!! Form::label('Background color', null, ['class' => 'fg-label']) !!}
                    {!! Form::text('background_color', '#00acc1', [
                        'class' => 'form-control cp-value',
                        'data-toggle' => 'dropdown',
                    ]) !!}

                    <div class="dropdown-menu">
                        <div class="color-picker" data-cp-default="#03A9F4"></div>
                    </div>
                    <i class="cp-value"></i>
                </div>
            </div>
        </div>

        <div class="input-group fg-float m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div class="fg-line">
                {!! Form::label('Font size', null, ['class' => 'fg-label']) !!}
                {!! Form::number('font_size', 16, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12">
                        {!! Form::label('Text alignment', null, ['class' => 'fg-label']) !!}
                    </div>
                    <div class="col-md-12">
                        {!! Form::select(
                           'text_align',
                           $alignments->map(function(\App\Models\Alignment\Alignment $item) {
                               return $item->label;
                           }),
                           null,
                           [
                               'class' => 'selectpicker',
                               'data-live-search' => 'true',
                           ]
                       ) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="input-group m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12">
                        {!! Form::label('Dimensions', null, ['class' => 'fg-label']) !!}
                    </div>
                    <div class="col-md-12">
                        {!! Form::select(
                            'dimensions',
                            $dimensions->map(function(\App\Models\Dimension\Dimensions $item) {
                                return $item->label;
                            }),
                            null,
                            [
                                'class' => 'selectpicker',
                                'data-live-search' => 'true',
                            ]
                        ) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="input-group m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12">
                        {!! Form::label('Position', null, ['class' => 'fg-label']) !!}
                    </div>
                    <div class="col-md-12">
                        {!! Form::select(
                           'position',
                           $positions->map(function(\App\Models\Position\Position $item) {
                               return $item->label;
                           }),
                           null,
                           [
                               'class' => 'selectpicker',
                               'data-live-search' => 'true',
                           ]
                       ) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="input-group fg-float m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div class="fg-line">
                {!! Form::label('Target URL', null, ['class' => 'fg-label']) !!}
                {!! Form::text('target_url', null, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group m-t-20">
            <div class="fg-line">
                {!! Form::button('<i class="zmdi zmdi-mail-send"></i> Save', [
                   'class' => 'btn btn-info waves-effect',
                   'type' => 'submit',
               ]) !!}
            </div>
        </div>

    </div>
    <div class="col-md-7 col-md-offset-1">
        <h5>Preview</h5>

        <div class="row p-relative">
            {!! HTML::image('/assets/img/website_mockup.png', 'Mockup', [
                'class' => 'preview-image',
            ]) !!}
            <div class="preview-box">
                <p class="preview-text">Preview banner text</p>
            </div>
        </div>
    </div>
</div>

@php
@endphp

<script type="text/javascript">
    var positions = JSON.parse('{!! json_encode($positions) !!}');
    var dimensions = JSON.parse('{!! json_encode($dimensions) !!}');
    var alignments = JSON.parse('{!! json_encode($alignments) !!}');

    window.Campaign.banner.init($('#banner-form'), positions, dimensions, alignments);



</script>