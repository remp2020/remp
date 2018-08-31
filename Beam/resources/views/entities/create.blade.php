@extends('layouts.app')

@section('title', 'Add entity')

@section('content')
    <div class="c-header">
        <h2>Add entity</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Add new segment</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($entity, ['route' => 'entities.store', 'method' => 'POST', 'class' => 'entity-form']) !!}
            @include('entities._form')
            {!! Form::close() !!}
        </div>
    </div>
@endsection
