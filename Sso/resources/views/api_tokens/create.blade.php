@extends('layouts.app')

@section('title', 'Add API token')

@section('content')

    <div class="c-header">
        <h2>API tokens</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new API token</h2>
        </div>
        <div class="card-body card-padding">

            {!! Form::model($apiToken, ['route' => 'api-tokens.store']) !!}
            @include('api_tokens._form')
            {!! Form::close() !!}

        </div>
    </div>
@endsection