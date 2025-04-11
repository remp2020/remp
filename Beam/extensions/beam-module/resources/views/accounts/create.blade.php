@extends('beam::layouts.app')

@section('title', 'Add account')

@section('content')

    <div class="c-header">
        <h2>Accounts</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new account <small></small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($account)->route('accounts.store')->open() }}
            @include('beam::accounts._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection