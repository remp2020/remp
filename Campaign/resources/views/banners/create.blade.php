@extends('layouts.app')

@section('title', 'Add account')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new banner <small>Lorem ipsum dolor sit amet, consectetur adipiscing elit</small></h2>
        </div>
        <div class="card-body card-padding">
            {!! Form::model($banner, ['route' => 'banners.store']) !!}
            @include('banners._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection