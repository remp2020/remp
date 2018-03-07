@extends('layouts.app')

@section('title', 'Properties')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')

    <div class="c-header">
        <h2>Properties</h2>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>List of properties <small></small></h2>
                <div class="actions">
                    <a href="{{ route('accounts.properties.create', $account->id) }}" class="btn palette-Cyan bg waves-effect">Add new property</a>
                </div>
            </div>

            {!! Widget::run('DataTable', [
                'colSettings' => ['name', 'uuid' => ['header' => 'token'], 'created_at' => ['header' => 'created at', 'render' => 'date']],
                'dataSource' => route('accounts.properties.json', $account),
                'rowActions' => [
                    ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                ],
            ]) !!}
        </div>
    </div>

@endsection