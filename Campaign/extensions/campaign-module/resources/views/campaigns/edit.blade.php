@extends('campaign::layouts.app')

@section('title', 'Edit campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns: {{ $campaign->name }}</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {{ html()->modelForm($campaign, 'PATCH')->route('campaigns.update', ['campaign' => $campaign, 'collection' => $collection])->attribute('id', 'campaign-form-root')->open() }}
        @include('campaign::campaigns._form', ['action' => 'edit'])
        {{ html()->closeModelForm() }}
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-8">
                <div class="card z-depth-1-top">
                    <div class="card-header">
                        <h2>Scheduled runs<small></small></h2>
                    </div>
                    <div class="card-body">
                        {!! Widget::run('DataTable', [
                        'colSettings' => [
                            'status' => [
                                'header' => 'Status',
                                'priority' => 1,
                                'render' => 'badge',
                            ],
                            'start_time' => [
                                'header' => 'Scheduled start date',
                                'render' => 'date',
                                'priority' => 2,
                            ],
                            'end_time' => [
                                'header' => 'Scheduled end date',
                                'render' => 'date',
                                'priority' => 2,
                            ],
                        ],
                        'dataSource' => route('campaign.schedule.json', ['campaign' => $campaign, 'active' => true]),
                        'order' => [1, 'desc'],
                        'displaySearchAndPaging' => false,
                        ]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
