@extends('beam::layouts.app')

@section('title', 'Edit account')

@section('content')

    <div class="c-header">
        <h2>Accounts</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit account <small>{{ $account->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($account, ['route' => ['accounts.update', $account], 'method' => 'PATCH']) !!}
            @include('beam::accounts._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection