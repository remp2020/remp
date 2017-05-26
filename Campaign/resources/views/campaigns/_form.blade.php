@php
/* @var $campaign \App\Campaign */
@endphp

@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
@endpush

<div class="row">
    <div class="col-md-6">
        <div class="input-group fg-float m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-account"></i></span>
            <div class="fg-line">
                {!! Form::label('Name', null, ['class' => 'fg-label']) !!}
                {!! Form::text('name', null, ['class' => 'form-control fg-input']) !!}
            </div>
        </div>

        <div class="input-group m-t-30">
            <span class="input-group-addon"><i class="zmdi zmdi-wallpaper"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12">
                        {!! Form::label('Banner', null, ['class' => 'fg-label']) !!}
                    </div>
                    <div class="col-md-12">
                        {!! Form::select(
                           'banner_id',
                           $banners->mapWithKeys(function(\App\Banner $banner) {
                               return [$banner->id => $banner->name];
                           })->toArray(),
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
            <span class="input-group-addon"><i class="zmdi zmdi-accounts-list"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12">
                        {!! Form::label('Segment', null, ['class' => 'fg-label']) !!}
                    </div>
                    <div class="col-md-12">
                        {!! Form::select(
                           'segment_id',
                            $segments->mapToGroups(function ($item) {
                                return [$item->group->name => [$item->code => $item->name]];
                            })->mapWithKeys(function($item, $key) {
                                return [$key => $item->collapse()];
                            })->toArray(),
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

        <div class="input-group fg-float m-t-30 checkbox">
            <label class="m-l-15">
                Activate
                {!! Form::checkbox('active') !!}
                <i class="input-helper"></i>
            </label>
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
</div>