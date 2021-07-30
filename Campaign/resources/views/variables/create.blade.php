@extends('layouts.app')

@section('title', 'Add variable')

@section('content')

    <div class="c-header">
        <h2>Variables</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Create variable</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($variable, ['route' => 'variables.store']) !!}
                @include('variables._form')
            {!! Form::close() !!}
        </div>
    </div>
@endsection