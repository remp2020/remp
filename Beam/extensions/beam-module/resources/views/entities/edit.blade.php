@extends('beam::layouts.app')

@section('title', 'Edit entity: ' . $entity->name)

@section('content')
    <div class="c-header">
        <h2>Edit entity: {{ $entity->name  }}</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {{ html()->modelForm($entity, 'PATCH')->route('entities.update', $entity)->attribute('class', 'entity-form')->open() }}
        @include('beam::entities._form')
        {{ html()->closeModelForm() }}
    </div>
@endsection
