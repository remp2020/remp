@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/default.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/highlight.min.js"></script>

    <style type="text/css">
        pre {
            padding: 0;
        }
        .hljs {
            overflow-x: scroll;
            white-space: unset;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        hljs.initHighlightingOnLoad();
    </script>
@endpush

@section('title', 'Edit property')

@section('content')

    <div class="c-header">
        <h2>Properties</h2>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show property <small>{{ $property->name }}</small></h2>
            </div>
            <div class="card-body card-padding">

            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>JS snippet</h2>
            </div>
            <div class="card-body card-padding">
                <pre><code class="html">{{ $snippet }}</code></pre>
            </div>
        </div>
    </div>

@endsection