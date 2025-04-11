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

            {{ html()->modelForm($apiToken)->route('api-tokens.store')->open() }}
            @include('api_tokens._form')
            {{ html()->closeModelForm() }}

        </div>
    </div>
@endsection