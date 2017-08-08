@extends('layouts.app')

@section('title', 'Add segment')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new segment</h2>
        </div>
        <div class="card-body card-padding">
            {!! Form::model($segment, ['route' => 'segments.store', 'id' => 'segment-form']) !!}
            @include('segments._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection