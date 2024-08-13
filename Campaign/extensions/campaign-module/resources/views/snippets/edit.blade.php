@extends('campaign::layouts.app')

@section('title', 'Edit snippet')

@section('content')

    <div class="c-header">
        <h2>Snippets</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit snippet</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($snippet, ['route' => ['snippets.update', 'snippet' => $snippet], 'method' => 'PATCH']) !!}
                @include('campaign::snippets._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection
