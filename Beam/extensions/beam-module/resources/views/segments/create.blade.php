@extends('beam::layouts.app')

@section('title', 'Add segment')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new segment</h2>
            <p>Try <a href="{{ route('segments.beta.create') }}" title="Beta version of new segment builder">beta version of new segment builder</a>.</p>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($segment, ['route' => 'segments.store']) !!}
            @include('beam::segments._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection
