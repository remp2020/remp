@extends('beam::layouts.app')

@section('title', 'Accounts')

@section('content')

    <div class="c-header">
        <h2>Accounts</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>All accounts <small></small></h2>
            <div class="actions">
                <a href="{{ route('accounts.create') }}" class="btn palette-Cyan bg waves-effect">Add new account</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                    'render' => 'link',
                ],
                'created_at' => [
                    'header' => 'created at',
                    'render' => 'date',
                    'priority' => 1,
                ],
            ],
            'dataSource' => route('accounts.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit', 'title' => 'Edit account'],
            ],
        ]) !!}

    </div>

@endsection
