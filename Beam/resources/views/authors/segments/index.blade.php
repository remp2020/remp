@extends('layouts.app')

@section('title', 'Author Segments')

@section('content')

    <div class="c-header">
        <h2>Author Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of author segments <small></small></h2>
            <div class="actions">
                <a href="{{ route('authorSegments.configuration') }}" class="btn palette-Cyan bg waves-effect">Configuration</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 2,
                ],
                'code' => [
                    'priority' => 2,
                ],
                'users_count' => [
                    'header' => 'Users count',
                    'priority' => 1,
                    'className' => 'text-right',
                ],
                'browsers_count' => [
                    'header' => 'Browsers count',
                    'priority' => 2,
                    'className' => 'text-right',
                ],
                'created_at' => [
                    'render' => 'date',
                    'header' => 'Created at',
                    'priority' => 3,
                    'className' => 'text-right',
                ],
            ],
            'dataSource' => route('authorSegments.json'),
            'order' => [2, 'desc'],
            'exportColumns' => [0,1,2,3,4],
        ]) !!}
    </div>

@endsection
