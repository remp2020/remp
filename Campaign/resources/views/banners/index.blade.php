@extends('layouts.app')

@section('title', 'Banners')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>List of banners <small></small></h2>
            <div class="actions">
                <a href="#modal-template-select" data-toggle="modal" class="btn palette-Cyan bg waves-effect">Add new banner</a>
            </div>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => ['name', 'dimensions', 'position'],
            'dataSource' => route('banners.json'),
            'rowActions' => [
                ['name' => 'show', 'class' => 'zmdi-palette-Cyan zmdi-eye'],
                ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
            ],
            'rowActionLink' => 'show',
        ]) !!}
    </div>

    <div class="modal" id="modal-template-select" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Select template</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="text-center">Custom HTML</h4>
                                </div>
                                <div class="card-body card-padding">
                                    <div class="preview" style="margin-top: 55px;">
                                        <i class="zmdi zmdi-language-html5 zmdi-hc-5x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="text-center">Medium rectangle</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview medium-rectangle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection