@extends('campaign::layouts.app')

@section('title', 'Collections')

@section('content')

    <div class="c-header">
        <h2>Collections</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of Collections
            </h2>
            <div class="actions">
                <a href="{{ route('collections.create') }}" data-toggle="modal" class="btn palette-Cyan bg waves-effect">Add new collection</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                    'render' => 'link',
                ],
                'campaigns' => [
                    'priority' => 2,
                    'render' => 'array',
                ]
            ],
            'dataSource' => route('collections.json'),
            'rowActions' => [
                ['name' => 'show', 'class' => 'zmdi-palette-Cyan zmdi-eye', 'title' => 'List campaigns'],
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit collection'],
                ['name' => 'destroy', 'class' => 'zmdi-palette-Cyan zmdi-delete confirm', 'title' => 'Remove collection', 'onclick' => 'return confirm(\'Are you sure you want to delete this collection?\')'],
            ],
            'order' => [1, 'desc']
        ]) !!}
    </div>
@endsection
