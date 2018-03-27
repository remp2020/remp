@extends('layouts.app')

@section('title', 'Add campaign')

@section('content')

    <div class="container">
        @include('flash::message')

        {!! Form::model($campaign, ['route' => 'campaigns.store']) !!}
        @include('campaigns._form')
        {!! Form::close() !!}
    </div>

@endsection