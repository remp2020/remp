@extends('campaign::layouts.app')

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

            {{ html()->modelForm($collection)->route('collections.store')->attribute('id', 'collection-form-root')->open() }}
                @include('campaign::collections._form', ['action' => 'create'])
            {{ html()->closeModelForm() }}
        </div>
    </div>
@endsection
