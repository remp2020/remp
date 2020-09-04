@extends('layouts.app')

@section('title', 'JWT Whitelist')

@section('content')

    <div class="c-header">
        <h2>JWT Whitelist</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>JWT Whitelist <small></small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            <div class="row">
                <div class="col-md-6">
                    <ul>
                    @foreach (explode(',', $jwtwhitelist) as $item)
                        <li><code>{{$item}}</code></li>
                     @endforeach
                    </ul>

                </div>
            </div>
        </div>
    </div>

@endsection
