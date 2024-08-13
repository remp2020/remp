@extends('campaign::layouts.app')

@section('title', 'Banners')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of banners <small></small></h2>
            <div class="actions">
                <a href="#modal-template-select" data-toggle="modal" class="btn palette-Cyan bg waves-effect">Add new banner</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                    'render' => 'link',
                ],
                'public_id' => [
                    'header' => 'Public ID',
                    'priority' => 1,
                ],
                'template' => [
                    'priority' => 2,
                ],
                'display_type' => [
                    'priority' => 2,
                ],
                'position' => [
                    'priority' => 2,
                    'searchable' => false,
                ],
                'created_at' => [
                    'header' => 'Created at',
                    'render' => 'date',
                    'priority' => 3,
                    'searchable' => false,
                ],
                'updated_at' => [
                    'header' => 'Updated at',
                    'render' => 'date',
                    'priority' => 4,
                    'searchable' => false,
                ],
            ],
            'dataSource' => route('banners.json'),
            'rowActions' => [
                ['name' => 'show', 'class' => 'zmdi-palette-Cyan zmdi-eye', 'title' => 'Show banner'],
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit banner'],
                ['name' => 'copy', 'class' => 'zmdi-palette-Cyan zmdi-copy', 'title' => 'Copy banner'],
            ],
            'rowHighlights' => [
                'active' => true
            ],
            'order' => [5, 'desc'],
        ]) !!}
    </div>

    @include('campaign::banners._template_modal')
@endsection
