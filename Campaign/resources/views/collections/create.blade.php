@extends('layouts.app')

@section('title', 'Add collection')

@section('content')

    <div class="c-header">
        <h2>Collections</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Create collection</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($collection, ['route' => 'collections.store', 'method' => 'POST', 'id' => 'collection-form-root']) !!}
                @include('collections._form', ['action' => 'create'])
            {!! Form::close() !!}
        </div>
    </div>
@endsection