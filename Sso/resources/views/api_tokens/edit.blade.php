@extends('layouts.app')

@section('title', 'Edit API token')

@section('content')

    <div class="c-header">
        <h2>API tokens: {{ $apiToken->name }}</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit API token <small>{{ $apiToken->name }}</small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($apiToken, 'PATCH')->route('api-tokens.update', $apiToken)->open() }}
            @include('api_tokens._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection