
@extends('layouts.app')

@section('title', 'Authors\' segments')

@section('content')

    <div class="c-header">
        <h2>Authors' segments</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Test page for configuration of authors' segments <small></small></h2>
        </div>

        <div class="card-body card-padding">
            @if(isset($results))
                Computation initiated, results will be sent to <b>{{ $email }}</b>.
            @else
                <p>Test page for finding right parameters for computation of authors' segments. <br />
                    There are 3 conditions that are taken into account when computing a specific author segment:
                </p>
                <ul>
                    <li>At least <b>X%</b> of all articles read by the user has to be from the given author</li>
                    <li>User has read at least <b>Y</b> articles per given period.</li>
                    <li>Average time spent on author articles is at least <b>Z</b> minutes.</li>
                </ul>
                <p>If all conditions are met, the user is put into the segment.</p>

                <h4>Configuration</h4>
                <p>
                    After the configuration is specified, computed results will show number of users/browsers present in the segment of each author.<br />
                    Results are computed asynchronously and sent to provided email.
                </p>

                <div class="row">
                    @include('authors.segments._form')
                </div>
            @endif
        </div>
    </div>

@endsection