@extends('layouts.app')

@section('title', 'Schedule new campaign run')

@section('content')

    <div class="c-header">
        <h2>Scheduler</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Schedule campaign run</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($schedule, ['route' => 'schedule.store']) !!}
            @include('schedule._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection