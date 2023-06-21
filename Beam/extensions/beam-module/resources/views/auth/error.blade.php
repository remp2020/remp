@extends('beam::layouts.auth')

@section('title', 'Error')

@section('content')

    <div class="lb-header palette-Teal bg">
        <i class="zmdi zmdi-account-circle"></i>
        <p>There was an error signing you in.</p>
        <p>If you believe this shouldn't happen, please contact your administrator.</p>
    </div>

    <div class="lb-body">
        {{ $message }}
    </div>

@endsection