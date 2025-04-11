@extends('campaign::layouts.app')

@section('title', 'Add campaign')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>

    <div class="container">
        @include('flash::message')

        {{ html()->modelForm($campaign)->route('campaigns.store', ['collection' => $collection])->attribute('id', 'campaign-form-root')->open() }}
        @include('campaign::campaigns._form', ['action' => 'create'])
        {{ html()->closeModelForm() }}
    </div>

@endsection
