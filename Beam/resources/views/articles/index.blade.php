@extends('layouts.app')

@section('title', 'Articles')

@section('content')

    <div class="c-header">
        <h2>Articles</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>All articles <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'title',
                'authors[, ].name' => ['header' => 'authors'],
                'sections[, ].name' => ['header' => 'sections'],
                'published_at' => ['header' => 'published at', 'render' => 'date'],
            ],
            'dataSource' => action('ArticleController@json'),
            'order' => [3, 'desc'],
        ]) !!}

    </div>

@endsection
