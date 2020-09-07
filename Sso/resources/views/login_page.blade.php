@extends('layouts.app')

@section('content')
    {foreach $providers as $provider}
    <a href="{ $provider.redirectUrl }">{ provider.name }</a>
    {/foreach}
@endsection