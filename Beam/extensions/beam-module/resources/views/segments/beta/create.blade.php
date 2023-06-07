@extends('beam::layouts.app')

@section('title', 'Add segment (beta version)')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new segment <i>(beta version)</i></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($segment, ['route' => 'segments.store']) !!}
            @include('beam::segments.beta._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection
