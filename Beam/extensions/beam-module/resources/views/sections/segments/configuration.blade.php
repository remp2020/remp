
@extends('beam::layouts.app')

@section('title', 'Sections\' segments')

@section('content')

    <div class="c-header">
        <h2>Sections' segments</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Test parameters<small></small></h2>
        </div>

        <div class="card-body card-padding">
            <p>Here you can quickly test arbitrary parameters without recomputing the actual segments. <br />
                After test parameters are specified, results containing number of users/browsers present in the segment of each section will be sent to provided email.
            </p>

            <div class="row">
                @include('beam::sections.segments._test_form')
            </div>
        </div>
    </div>

@endsection
