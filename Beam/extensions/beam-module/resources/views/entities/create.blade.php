@extends('beam::layouts.app')

@section('title', 'Add entity')

@section('content')
    <div class="c-header">
        <h2>Add entity</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {{ html()->modelForm($entity)->route('entities.store')->attribute('class', 'entity-form')->open() }}
        @include('beam::entities._form')
        {{ html()->closeModelForm() }}
    </div>
@endsection
