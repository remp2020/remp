@extends('layouts.app')

@section('title', 'Edit segment')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit segment / <small>{{ $segment->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            {!! Form::model($segment, ['route' => ['segments.update', $segment], 'method' => 'PATCH']) !!}
            @include('segments._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection