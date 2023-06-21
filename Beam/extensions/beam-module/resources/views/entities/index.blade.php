@extends('beam::layouts.app')

@section('title', 'Entities')

@section('content')

    <div class="c-header">
        <h2>Entities</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of entities <small></small></h2>
            <div class="actions">
                <a href="{{ route('entities.create') }}" class="btn palette-Cyan bg waves-effect">Add new entity</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'orderable' => true,
                    'priority' => 1,
                    'render' => 'link',
                ],
                'params' => [
                    'header' => 'Params',
                    'orderable' => false,
                    'priority' => 2,
                    'render' => 'array',
                ]
            ],
            'dataSource' => route('entities.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit entity']
            ]
        ]) !!}

    </div>

@endsection
