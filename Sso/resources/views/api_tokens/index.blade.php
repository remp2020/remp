@extends('layouts.app')

@section('title', 'API Tokens')

@section('content')

    <div class="c-header">
        <h2>API Tokens</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of API Tokens <small></small></h2>
            <div class="actions">
                <a href="{{ route('api-tokens.create') }}" class="btn palette-Cyan bg waves-effect">Add new API Token</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                ],
                'token' => [
                    'priority' => 2,
                ],
                'active' => [
                    'header' => 'Is active',
                    'render' => 'boolean',
                    'priority' => 2,
                ],
                'created_at' => [
                    'header' => 'Created at',
                    'render' => 'date',
                    'priority' => 3,
                ],
                'updated_at' => [
                    'header' => 'Updated at',
                    'render' => 'date',
                    'priority' => 4,
                ],
            ],
            'dataSource' => route('api-tokens.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                ['name' => 'destroy', 'class' => 'zmdi-palette-Cyan zmdi-delete'],
            ],
            'rowActionLink' => 'show',
            'order' => [4, 'desc'],
        ]) !!}
    </div>
@endsection