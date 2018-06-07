@extends('layouts.app')

@section('title', 'Articles - Newsletters')

@section('content')

    <div class="c-header">
        <h2>Articles - Create newsletter</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Select your newsletter parameters <small></small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')
            {!! Form::open(['route' => ['articles.newsletter.store']]) !!}
            @include('articles._form_newsletter')
            {!! Form::close() !!}
        </div>

    </div>
@endsection
