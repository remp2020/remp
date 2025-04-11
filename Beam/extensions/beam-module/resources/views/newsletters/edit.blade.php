@extends('beam::layouts.app')

@section('title', 'Edit newsletter')

@section('content')

    <div class="c-header">
        <h2>Newsletters</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Edit newsletter <small></small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')
            {{ html()->modelForm($newsletter, 'PATCH')->route('newsletters.update', $newsletter)->open() }}
            @include('beam::newsletters._form')
            {{ html()->closeModelForm() }}
        </div>

    </div>
@endsection
