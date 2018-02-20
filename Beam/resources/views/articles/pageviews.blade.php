@extends('layouts.app')

@section('title', 'Articles - Pageview stats')

@section('content')

    <div class="c-header">
        <h2>Articles - Pageview stats</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Pageview stats <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'title',
                'pageviews_sum' => ['header' => 'pageviews'],
                'timespent_sum' => ['header' => 'total time read', 'render' => 'duration'],
                'avg_sum' => ['header' => 'avg', 'render' => 'duration'],
                'authors[, ].name' => ['header' => 'authors', 'orderable' => false, 'filter' => $authors],
                'sections[, ].name' => ['header' => 'sections', 'orderable' => false, 'filter' => $sections],
                'published_at' => ['header' => 'published at', 'render' => 'date'],
            ],
            'dataSource' => route('articles.dtPageviews'),
            'order' => [4, 'desc'],
        ]) !!}

    </div>

@endsection
