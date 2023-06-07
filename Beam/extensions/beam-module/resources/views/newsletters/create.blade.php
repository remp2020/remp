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
            {!! Form::model($newsletter, ['route' => 'newsletters.store']) !!}
            @include('beam::newsletters._form')
            {!! Form::close() !!}
        </div>

    </div>
@endsection
