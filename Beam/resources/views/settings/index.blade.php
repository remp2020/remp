@extends('layouts.app')

@section('title', 'Settings')

@section('content')

    <div class="c-header">
        <h2>Settings</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Settings <small></small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            <form method="post" action="{{ route('settings.update') }}">
                {!! Form::token() !!}

                @foreach($configs as $config)

                    <div class="form-group fg-float m-b-30">
                        <div class="fg-line fg-toggled">
                            <input type="text" name="settings[{{$config->name}}]"  value="{{$config->value}}" class="form-control fg-input">
                            <label class="fg-label">{{ $config->display_name ?? $config->name}}</label>
                        </div>
                    </div>

                @endforeach

                <button type="submit" name="save" value="Save" class="btn btn-info waves-effect">
                    <i class="zmdi zmdi-mail-send"></i> Save
                </button>
            </form>
        </div>
    </div>

@endsection
