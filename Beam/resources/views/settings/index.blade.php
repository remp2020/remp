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

            <div class="row">
                <div class="col-md-6">

                    <form method="post" action="{{ route('settings.update') }}">
                        {{ csrf_field() }}

                        @foreach($configs as $config)
                            <p class=" f-500 m-b-10"><span class="c-black">{{ $config->display_name ?? $config->name}}</span>
                                <small>
                                    @if($config->description)
                                        <br />{{$config->description}}
                                    @endif
                                    @if(in_array($config->name, \App\Console\Commands\ComputeAuthorsSegments::ALL_CONFIGS))
                                        <br /> You can test this parameter at <a href="{{route('authorSegments.test')}}">Author segments testing page</a>
                                    @endif
                                </small>
                            </p>

                            <div class="form-group">
                                <div class="fg-line">
                                    <input type="text" name="settings[{{$config->name}}]"  value="{{$config->value}}" class="form-control fg-input">
                                </div>
                            </div>
                        @endforeach

                        <button type="submit" name="save" value="Save" class="btn btn-info waves-effect">
                            <i class="zmdi zmdi-mail-send"></i> Save
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection
