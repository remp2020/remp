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

@section('title', 'Campaigns')

@section('content')

    <div class="c-header">
        <h2>Campaigns</h2>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Campaign list <small></small></h2>
                    <div class="actions">
                        <a href="{{ route('campaigns.create') }}" class="btn palette-Cyan bg waves-effect">Add new campaign</a>
                    </div>
                </div>

                <div class="card-body">
                    {!! Widget::run('DataTable', [
                    'colSettings' => [
                        'name',
                        'banner.name' => [
                            'header' => 'Banner'
                        ],
                        'segments' => [
                            'header' => 'Segments',
                            'render' => 'array',
                            'renderParams' => ['column' => 'code']
                        ],
                        'active' => [
                            'header' => 'Is active',
                            'render' => 'boolean'
                        ],
                        'signed_in' => [
                            'header' => 'Signed in',
                            'render' => 'boolean',
                        ],
                    ],
                    'dataSource' => route('campaigns.json'),
                    'rowActions' => [
                        ['name' => 'edit', 'class' => 'zmdi-palette-Cyan zmdi-edit'],
                    ],
                    'rowActionLink' => 'edit',
                ]) !!}
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>
                        Campaign display JS snippet
                        <a href="#snippet" class="btn palette-Cyan bg waves-effect" data-toggle="collapse" aria-controls="snippet">Toggle</a>
                    </h2>
                </div>

                <div class="card-body card-padding collapse" id="snippet">
                    <pre><code class="html">{{ $snippet }}</code></pre>
                </div>
            </div>
        </div>
    </div>


@endsection