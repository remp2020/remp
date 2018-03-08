@extends('layouts.app')

@section('title', 'Add banner')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Add new banner</h2>
            <div class="actions">
                <a href="#modal-template-select" data-toggle="modal">
                    <button type="button" class="btn palette-Cyan bg waves-effect pull-right">Change template</button>
                </a>
            </div>
        </div>
    </div>

    @include('flash::message')

    {!! Form::model($banner, ['route' => 'banners.store']) !!}
    @include('banners._form')
    {!! Form::close() !!}

    @include('banners._template_modal')
@endsection