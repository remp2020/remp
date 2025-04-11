@extends('beam::layouts.app')

@section('title', 'Create newsletter')

@section('content')

    <div class="c-header">
        <h2>Newsletters</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Create new newsletter <small></small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')
            {{ html()->modelForm($newsletter)->route('newsletters.store')->open() }}
            @include('beam::newsletters._form')
            {{ html()->closeModelForm() }}
        </div>

    </div>
@endsection
