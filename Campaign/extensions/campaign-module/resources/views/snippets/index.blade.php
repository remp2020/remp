@extends('campaign::layouts.app')

@section('title', 'Snippets')

@section('content')

    <div class="c-header">
        <h2>Snippets</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of snippets
                <small v-pre>
                    These snippets are supported in your banner template contents, inline JS/CSS snippets and URLs of external JS/CSS files.<br>
                    You can use created snippets by adding <code>&#123;&#123;&nbsp;snippet_name&nbsp;&#125;&#125;</code> to fields which are marked as supported.
                </small>
            </h2>
            <div class="actions">
                <a href="{{ route('snippets.create') }}" data-toggle="modal" class="btn palette-Cyan bg waves-effect">Add new snippet</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                    'render' => 'link',
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
            'dataSource' => route('snippets.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit snippet'],
            ],
            'order' => [2, 'desc']
        ]) !!}
    </div>
@endsection
