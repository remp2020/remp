@extends('campaign::layouts.app')

@section('title', 'Schedule new campaign run')

@section('content')

    <div class="c-header">
        <h2>SCHEDULES</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit scheduled run <small>{{ $schedule->campaign->name }}</small></h2>
            <div class="actions">
                <a href="{{ route('campaigns.show', ['campaign' => $schedule->campaign])  }}" class="btn palette-Cyan bg waves-effect">
                    <i class="zmdi zmdi-palette-Cyan zmdi-eye"></i> Show campaign
                </a>
                <a href="{{ route('campaigns.edit', ['campaign' => $schedule->campaign])  }}" class="btn palette-Cyan bg waves-effect">
                    <i class="zmdi zmdi-palette-Cyan zmdi-edit"></i> Edit campaign
                </a>
            </div>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($schedule, 'PATCH')->route('schedule.update', ['schedule' => $schedule, 'collection' => $collection])->open() }}
            @include('campaign::schedule._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

@endsection
