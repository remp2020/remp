@extends('layouts.app')

@section('title', 'Campaigns')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Lorem ipsum <small>Lorem ipsum dolor sit amet, consectetur adipiscing elit</small></h2>
            <div class="actions">
                <a href="{{ route('campaigns.create') }}" class="btn palette-Cyan bg waves-effect">Add new campaign</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => ['name', 'segment_id', 'active'],
            'dataSource' => route('campaigns.json'),
            'rowActions' => [
                ['name' => 'show', 'class' => 'zmdi-palette-Cyan zmdi-eye'],
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
            ],
        ]) !!}
    </div>

@endsection