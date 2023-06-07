@extends('beam::layouts.app')

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
                <div class="list-group lg-alt lg-even-black">
                    @foreach($events as $event)
                        <div class="list-group-item media">
                            <div class="media-body">
                                <div class="lgi-heading">
                                    <small><date-formatter format="l LT" date="{{$event->time}}"></date-formatter></small>
                                    {{$event->name}}
                                </div>
                                <small class="lgi-text"></small>
                                @if($event->tags)
                                    <ul class="lgi-attrs">
                                        @foreach($event->tags as $tag)
                                            <li>
                                                @if(isset($tag->href))
                                                    <a href="{{$tag->href}}">{{$tag->title}}</a>
                                                @else
                                                    {{$tag->title}}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
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
