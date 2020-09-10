@extends('layouts.login')

@section('content')
    <style type="text/css">
        .sso-provider {
            text-decoration: none;
            text-transform: uppercase;
        }
    </style>

    @foreach ($providerRedirects as $name => $redirectUrl)
        <a href="{{ $redirectUrl }}" class="sso-provider">
            <div class="btn palette-Cyan bg">{{ $name }}</div>
        </a>
    @endforeach
@endsection