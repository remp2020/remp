@extends('campaign::layouts.app')

@section('title', 'Edit collection')

@section('content')

    <div class="c-header">
        <h2>Collections</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Edit collection</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($collection, ['route' => ['collections.update', $collection], 'method' => 'PATCH', 'id' => 'collection-form-root']) !!}
                @include('campaign::collections._form', ['action' => 'edit'])
            {!! Form::close() !!}
        </div>
    </div>
@endsection
