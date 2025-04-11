@extends('beam::layouts.app')

@section('title', 'Add property')

@section('content')

    <div class="c-header">
        <h2>Properties</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new property <small></small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($property)->route('accounts.properties.store', $account)->open() }}
            @include('beam::properties._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection