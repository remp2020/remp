@extends('beam::layouts.app')

@section('title', 'Edit property')

@section('content')

    <div class="c-header">
        <h2>Properties</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit property <small>{{ $property->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($property, ['route' => ['accounts.properties.update', $account, $property], 'method' => 'PATCH']) !!}
            @include('beam::properties._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection