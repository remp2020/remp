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

            {{ html()->modelForm($collection, 'PATCH')->route('collections.update', $collection)->attribute('id', 'collection-form-root')->open() }}
                @include('campaign::collections._form', ['action' => 'edit'])
            {{ html()->closeModelForm() }}
        </div>
    </div>
@endsection
