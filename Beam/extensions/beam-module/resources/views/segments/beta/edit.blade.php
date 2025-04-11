@extends('beam::layouts.app')

@section('title', 'Edit segment (beta version)')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit segment / <small>{{ $segment->name }}</small> <i>(beta version)</i></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($segment, 'PATCH')->route('segments.update', $segment)->open() }}
            @include('beam::segments.beta._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection