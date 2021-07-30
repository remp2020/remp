@extends('layouts.app')

@section('title', 'Variables')

@section('content')

    <div class="c-header">
        <h2>Variables</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of variables
                <small v-pre>
                    These variables are supported in your banner template contents, inline JS/CSS snippets and URLs of external JS/CSS files.<br>
                    You can use created variables by adding <code>&#123;&#123;&nbsp;variable_name&nbsp;&#125;&#125;</code> to fields which are marked as supported.
                </small>
            </h2>
            <div class="actions">
                <a href="{{ route('variables.create') }}" data-toggle="modal" class="btn palette-Cyan bg waves-effect">Add new variable</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
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
            'dataSource' => route('variables.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit variable'],
            ],
            'order' => [2, 'desc']
        ]) !!}
    </div>
@endsection
