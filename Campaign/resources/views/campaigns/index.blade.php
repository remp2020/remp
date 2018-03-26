@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Campaign list <small></small></h2>
                    <div class="actions">
                        <a href="{{ route('campaigns.create') }}" class="btn palette-Cyan bg waves-effect">Add new campaign</a>
                    </div>
                </div>

                <div class="card-body">
                    {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'name',
                        'banner' => [
                            'header' => 'Banner'
                        ],
                        'alt_banner' => [
                            'header' => 'Banner B'
                        ],
                        'segments' => [
                            'header' => 'Segments'
                        ],
                        'countries' => [
                            'header' => 'Countries'
                        ],
                        'active' => [
                            'header' => 'Is active'
                        ],
                        'signed_in' => [
                            'header' => 'Signed in',
                            'render' => 'boolean',
                        ],
                        'devices' => [
                            'header' => 'Devices'
                        ],
                        'created_at' => [
                            'header' => 'Created at',
                            'render' => 'date',
                        ],
                        'updated_at' => [
                            'header' => 'Updated at',
                            'render' => 'date',
                        ],
                    ],
                    'rowHighlights' => [
                        'active' => true
                    ],
                    'dataSource' => route('campaigns.json'),
                    'rowActions' => [
                        ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                        ['name' => 'copy', 'class' => 'zmdi-palette-Cyan zmdi-copy'],
                    ],
                    'order' => [9, 'desc'],
                ]) !!}
                </div>
            </div>
        </div>
    </div>

@endsection
