@extends('layouts.app')

@section('title', 'Conversions')

@section('content')

    <div class="c-header">
        <h2>Conversions</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>All conversions <small></small></h2>
        </div>

        {!! Widget::run('DataTable', [
            'colSettings' => [
                'article.title' => ['header' => 'article'],
                'article.authors[, ].name' => ['header' => 'authors'],
                'article.sections[, ].name' => ['header' => 'sections'],
                'amount' => ['header' => 'amount'],
                'currency' => ['header' => 'currency'],
                'paid_at' => ['header' => 'paid at', 'render' => 'date'],
            ],
            'dataSource' => route('conversions.json'),
            'order' => [5, 'desc'],
        ]) !!}

    </div>

@endsection
