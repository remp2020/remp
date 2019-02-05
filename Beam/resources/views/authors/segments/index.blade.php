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
                <a href="{{ route('authorSegments.test') }}" class="btn palette-Cyan bg waves-effect">Configuration testing page</a>
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
                ],
                'browsers_count' => [
                    'header' => 'Browsers count',
                    'priority' => 2,
                ],
                'created_at' => [
                    'render' => 'date',
                    'header' => 'Created at',
                    'priority' => 3,
                ],
                'updated_at' => [
                    'render' => 'date',
                    'header' => 'Updated at',
                    'priority' => 1,
                ],
            ],
            'dataSource' => route('authorSegments.json'),
            'order' => [1, 'desc'],
        ]) !!}
    </div>

@endsection
