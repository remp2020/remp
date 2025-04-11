@extends('campaign::layouts.app')

@section('title', 'Edit banner')

@section('content')

    <div class="c-header">
        <h2>Banners</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit banner <small>{{ $banner->name }}</small></h2>
            <div class="actions">
                <a href="{{ route('banners.show', $banner) }}" class="btn palette-Cyan bg waves-effect">
                    <i class="zmdi zmdi-palette-Cyan zmdi-eye"></i> Show
                </a>
                <a href="{{ route('banners.copy', $banner) }}" class="btn palette-Cyan bg waves-effect">
                    <i class="zmdi zmdi-palette-Cyan zmdi-copy"></i> Copy
                </a>
            </div>
        </div>
    </div>

    {{ html()->modelForm($banner, 'PATCH')->route('banners.update', $banner)->open() }}
    @include('campaign::banners._form')
    {{ html()->closeModelForm() }}

@endsection
