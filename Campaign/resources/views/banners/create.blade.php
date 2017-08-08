@extends('layouts.app')

@section('title', 'Add banner')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new banner</h2>
        </div>
    </div>

    {!! Form::model($banner, ['route' => 'banners.store']) !!}
    @include('banners._form')
    {!! Form::close() !!}

@endsection