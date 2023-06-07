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

            {!! Form::model($account, ['route' => 'accounts.store']) !!}
            @include('beam::accounts._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection