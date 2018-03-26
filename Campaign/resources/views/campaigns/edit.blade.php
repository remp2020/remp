@extends('layouts.app')

@section('title', 'Edit campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns: {{ $campaign->name }}</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit campaign <small>{{ $campaign->name }}</small></h2>
        </div>

        <div class="card-body card-padding">
            @include('flash::message')

            {!! Form::model($campaign, ['route' => ['campaigns.update', $campaign], 'method' => 'PATCH']) !!}
            @include('campaigns._form')
            {!! Form::close() !!}
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Scheduled runs<small></small></h2>
        </div>
        <div class="card-body">
            {!! Widget::run('DataTable', [
            'colSettings' => [
                'campaign' => [
                    'header' => 'Campaign',
                ],
                'start_time' => [
                    'header' => 'Scheduled start date',
                    'render' => 'date',
                ],
                'end_time' => [
                    'header' => 'Scheduled end date',
                    'render' => 'date',
                ],
                'status' => [
                    'header' => 'Status',
                ],
            ],
            'dataSource' => route('campaign.schedule.json', ['campaign' => $campaign]),
            ]) !!}
        </div>
    </div>

@endsection