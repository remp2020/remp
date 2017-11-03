@extends('layouts.app')

@section('title', 'Schedule new campaign run')

@section('content')

    <div class="c-header">
        <h2>Scheduler</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit scheduled run <small>{{ $schedule->campaign->name }}</small></h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($schedule, ['route' => ['schedule.update', 'schedule' => $schedule], 'method' => 'PATCH']) !!}
            @include('schedule._form')
            {!! Form::close() !!}
        </div>
    </div>

@endsection