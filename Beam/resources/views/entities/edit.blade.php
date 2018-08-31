@extends('layouts.app')

@section('title', 'Edit entity: ' . $entity->name)

@section('content')
    <div class="c-header">
        <h2>Edit entity: {{ $entity->name  }}</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Edit entity / <small>{{ $entity->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($entity, ['route' => ['entities.update', $entity], 'method' => 'PATCH', 'class' => 'entity-form']) !!}
            @include('entities._form')
            {!! Form::close() !!}
        </div>
    </div>
@endsection
