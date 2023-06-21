@extends('beam::layouts.app')

@section('title', 'Section Segments')

@section('content')

    <div class="c-header">
        <h2>Section Segments</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of section segments <small></small></h2>
            <div class="actions">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle btn btn-info palette-Cyan bg waves-effect" data-toggle="dropdown" aria-expanded="false"><i class="zmdi zmdi-settings"></i> More options</a>
                    <ul class="dropdown-menu">
                        <li role="presentation"><a role="menuitem" tabindex="-1" href="{{ $sectionSegmentsSettingsUrl }}">Configuration</a></li>
                        <li role="presentation"><a role="menuitem" tabindex="-1" href="{{ route('sectionSegments.testingConfiguration') }}">Test parameters</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'name' => [
                    'priority' => 2,
                    'render' => 'text',
                ],
                'code' => [
                    'priority' => 2,
                    'render' => 'text',
                ],
                'users_count' => [
                    'header' => 'Users count',
                    'priority' => 1,
                    'className' => 'text-right',
                ],
                'browsers_count' => [
                    'header' => 'Browsers count',
                    'priority' => 2,
                    'className' => 'text-right',
                ],
                'created_at' => [
                    'render' => 'date',
                    'header' => 'Created at',
                    'priority' => 3,
                    'className' => 'text-right',
                ],
            ],
            'dataSource' => route('sectionSegments.json'),
            'order' => [2, 'desc'],
            'exportColumns' => [0,1,2,3,4],
        ]) !!}
    </div>

@endsection
