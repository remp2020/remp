@extends('beam::layouts.app')

@section('title', 'Edit segment')

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit segment / <small>{{ $segment->name }}</small></h2>
            <p>Try <a href="{{ route('segments.beta.edit', $segment) }}" title="Beta version of new segment builder">beta version of new segment builder</a>.</p>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($segment, 'PATCH')->route('segments.update', $segment)->open() }}
            @include('beam::segments._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection