@extends('layouts.app')

@section('title', 'Conversion - ' . $conversion->id)

@section('content')



    <div class="c-header">
        <h2>Conversion detail</h2>
    </div>

    <div id="conversion-detail" class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>
                    Conversion #{{ $conversion->id }}
                </h2>

            </div>
            <div class="card-body card-padding">
                <dl class="dl-horizontal">
                    <dt>Article</dt>
                    <dd><a href="{{ route('articles.show', $conversion->article->id) }}">{{ $conversion->article->title }}</a></dd>

                    <dt>User ID</dt>
                    <dd>{{ $conversion->user_id }}</dd>

                    <dt>Amount</dt>
                    <dd>{{ number_format($conversion->amount, 2) }} {{ $conversion->currency }}</dd>

                    <dt>Paid at</dt>
                    <dd><date-formatter date="{{$conversion->paid_at}}"></date-formatter></dd>
                </dl>

                <h4>User path</h4>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#conversion-detail",
            components: {
                DateFormatter
            }
        })
    </script>

@endsection
