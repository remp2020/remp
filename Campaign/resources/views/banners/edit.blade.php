@extends('layouts.app')

@section('title', 'Edit banner')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit banner <small>{{ $banner->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            @component('banners._vue_form')
            @endcomponent

            {!! Form::model($banner, ['route' => ['banners.update', $banner], 'method' => 'PATCH', 'id' => 'banner-form']) !!}
            @include('banners._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection