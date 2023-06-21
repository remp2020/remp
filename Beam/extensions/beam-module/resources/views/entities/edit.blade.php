@extends('beam::layouts.app')

@section('title', 'Edit entity: ' . $entity->name)

@section('content')
    <div class="c-header">
        <h2>Edit entity: {{ $entity->name  }}</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {!! Form::model($entity, ['route' => ['entities.update', $entity], 'method' => 'PATCH', 'class' => 'entity-form']) !!}
        @include('beam::entities._form')
        {!! Form::close() !!}
    </div>
@endsection
