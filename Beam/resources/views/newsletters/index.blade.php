@extends('layouts.app')

@section('title', 'Newsletters')

@section('content')

    <div class="c-header">
        <h2>Newsletters</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of newsletters <small></small></h2>
            <div class="actions">
                <a href="{{ route('newsletters.create') }}" class="btn palette-Cyan bg waves-effect">Add new newsletter</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 1,
                ],
                'segment' => [
                    'priority' => 2,
                    'render' => 'segmentCode'
                ],
                'starts_at' => [
                    'render' => 'date',
                    'header' => 'Starts at',
                    'priority' => 3,
                ],
                'created_at' => [
                    'render' => 'date',
                    'header' => 'Created at',
                    'priority' => 3,
                ],
                'updated_at' => [
                    'render' => 'date',
                    'header' => 'Updated at',
                    'priority' => 2,
                ],
            ],
            'dataSource' => route('newsletters.json'),
            'rowActions' => [
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit']
            ],
            'rowActionLink' => 'edit',
            'order' => [4, 'desc'],
        ]) !!}
    </div>

    <script>
        $.fn.dataTables.render.segmentCode = function () {
            return function (data) {
                return data.split("::")[1];
            }
        }
    </script>

@endsection