@extends('layouts.app')

@section('title', 'Add entity')

@section('content')
    <div class="c-header">
        <h2>Add entity</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {!! Form::model($entity, ['route' => 'entities.store', 'method' => 'POST', 'class' => 'entity-form']) !!}
        @include('entities._form')
        {!! Form::close() !!}
    </div>
@endsection
