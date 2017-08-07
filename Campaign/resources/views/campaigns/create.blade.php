@extends('layouts.app')

@section('title', 'Add campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new campaign</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($campaign, ['route' => 'campaigns.store', 'id' => 'campaign-form']) !!}
            @include('campaigns._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection