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

            {{ html()->modelForm($snippet, 'PATCH')->route('snippets.update', ['snippet' => $snippet])->open() }}
                @include('campaign::snippets._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection
