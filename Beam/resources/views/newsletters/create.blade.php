@extends('layouts.app')

@section('title', 'Newsletters')

@section('content')

    <div class="c-header">
        <h2>Newsletter - create</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Select your newsletter parameters <small></small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')
            {!! Form::open(['route' => ['newsletters.store']]) !!}
            @include('newsletters._form')
            {!! Form::close() !!}
        </div>

    </div>
@endsection
