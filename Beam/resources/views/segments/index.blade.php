@extends('layouts.app')

@section('title', 'Segments')

@section('sidebar')
    @parent

    <p>This is appended to the master sidebar.</p>
@endsection

@section('content')

    <div class="c-header">
        <h2>Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of segments <small></small></h2>
            <div class="actions">
                <a href="{{ route('segments.create') }}" class="btn palette-Cyan bg waves-effect">Add new segment</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name',
                'code',
                'active' => ['render' => 'boolean', 'header' => 'Is active'],
                'created_at' => ['render' => 'date', 'header' => 'Created at'],
                'updated_at' => ['render' => 'date', 'header' => 'Updated at'],
            ],
            'dataSource' => route('segments.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                ['name' => 'copy', 'class' => 'zmdi-palette-Cyan zmdi-copy'],
            ],
            'rowActionLink' => 'edit',
            'order' => [4, 'desc'],
        ]) !!}
    </div>

@endsection
