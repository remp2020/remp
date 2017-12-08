@extends('layouts.app')

@section('title', 'Edit property')

@section('content')

    <div class="c-header">
        <h2>Properties</h2>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show property <small>{{ $property->name }}</small></h2>
            </div>
            <div class="card-body card-padding">

            </div>
        </div>
    </div>

@endsection