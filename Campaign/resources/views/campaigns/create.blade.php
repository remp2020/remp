@extends('layouts.app')

@section('title', 'Add campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {!! Form::model($campaign, ['route' => 'campaigns.store', 'id' => 'campaign-form-root']) !!}
        @include('campaigns._form', ['action' => 'create'])
        {!! Form::close() !!}
    </div>

@endsection