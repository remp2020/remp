@extends('campaign::layouts.app')

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

    {{ html()->modelForm($banner)->route('banners.store')->open() }}
    @include('campaign::banners._form')
    {{ html()->closeModelForm() }}

    @include('campaign::banners._template_modal')
@endsection
