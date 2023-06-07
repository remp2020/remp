
@extends('beam::layouts.app')

@section('title', 'Sections\' segments')

@section('content')

    <div class="c-header">
        <h2>Sections' segments</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Configuration <small></small></h2>
        </div>

        <div class="card-body card-padding">
            Computation initiated, results will be sent to <b>{{ $email }}</b>.
        </div>
    </div>

@endsection