@extends('layouts.app')

@section('title', 'Accounts')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')

    <div class="c-header">
        <h2>Accounts</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Lorem ipsum <small>Lorem ipsum dolor sit amet, consectetur adipiscing elit</small></h2>
            <div class="actions">
                <a href="{{ route('accounts.create') }}" class="btn palette-Cyan bg waves-effect">Add new account</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => ['name', 'created_at' => ['header' => 'created at', 'render' => 'date']],
            'dataSource' => action('AccountController@json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
            ],
        ]) !!}

    </div>

@endsection