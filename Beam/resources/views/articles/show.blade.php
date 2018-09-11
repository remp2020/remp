@extends('layouts.app')

@section('title', 'Show article - ' . $article->title)

@section('content')

    <div class="c-header">
        <h2>Article Details</h2>
    </div>


    <div class="card" id="profile-main">
        <div class="pm-overview" style="overflow: visible;">
            <div class="pmo-pic">
                <div >
                    <a href="{{$article->url}}">
                        <img class="img-responsive" src="{{$article->image_url}}" alt="">
                    </a>
                </div>
            </div>

            <div class="pmo-block" style="margin-top: 0px; padding-top:0px">
                <h2>{{ $article->title }}</h2>
                <b>{{$article->authors->implode('name', ', ')}}</b><br />
                {{$article->published_at->toDateTimeString()}}
            </div>
        </div>

        <div class="pm-body clearfix">
            <div class="pmb-block">
                <div class="pmbb-header">
                    <h2><i class="zmdi zmdi-library m-r-5"></i> Article Information</h2>
                </div>
                <div class="pmbb-body p-l-30">
                    <div class="pmbb-view">
                        <dl class="dl-horizontal">
                            <dt>External ID</dt>
                            <dd>{{$article->external_id}}</dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>URL</dt>
                            <dd><a href="{{$article->url}}">{{$article->url}}</a></dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>Title</dt>
                            <dd>{{$article->title}}</dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>Authors</dt>
                            <dd>
                                {{$article->authors->implode('name', ', ')}}
                            </dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>Published at</dt>
                            <dd>
                                {{$article->published_at->toDateTimeString()}}
                            </dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>Created at</dt>
                            <dd>
                                {{$article->created_at->toDateTimeString()}}
                            </dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>Updated at</dt>
                            <dd>
                                {{$article->updated_at->toDateTimeString()}}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="pmb-block">
                <div class="pmbb-header">
                    <h2><i class="zmdi zmdi-money m-r-5"></i> Conversions</h2>
                </div>
                <div class="pmbb-body p-l-30">
                    <div class="pmbb-view">
                        <dl class="dl-horizontal">
                            <dt>Total conversions</dt>
                            <dd>{{$article->conversions->count()}}</dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>
                                <span data-toggle="tooltip" data-placement="top" title="" data-original-title="Ratio of new conversions and unique visitors">Conversion rate</span>
                            </dt>
                            <dd>{{number_format($conversionRate, 2)}} %</dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt>New subscriptions</dt>
                            <dd>{{$newSubscriptionsCount}}</dd>
                        </dl>
                        <dl class="dl-horizontal">
                            <dt><span data-toggle="tooltip" data-placement="top" title="" data-original-title="Users who already had a subscription in the past">Renewed subscriptions</span></dt>
                            <dd>{{$renewSubscriptionsCount}}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="pmb-block">
                <div class="pmbb-header">
                    <h2><i class="zmdi zmdi-equalizer m-r-5"></i> Retention</h2>
                </div>
                <div class="pmbb-body p-l-30">
                    <div class="pmbb-view">
                        <dl class="dl-horizontal">
                            <dt>Pageviews subscribers</dt>
                            <dd>{{$article->pageviews_subscribers}} (<b>{{number_format($pageviewsSubscribersToAllRatio, 4)}}%</b> of all views)</dd>
                        </dl>

                        <dl class="dl-horizontal">
                            <dt>Pageviews signed-in</dt>
                            <dd>{{$article->pageviews_signed_in}}</dd>
                        </dl>

                        <dl class="dl-horizontal">
                            <dt>Pageviews all</dt>
                            <dd>{{$article->pageviews_all}}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-chart">

        <div class="card-header">
            <h2>Article Traffic
            </h2>
        </div>
        <div class="card-body card-padding">
            <div id="article-graph">
                <article-histogram :url="url">
                </article-histogram>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#article-graph",
            components: {
                ArticleHistogram
            },
            data: function() {
                return {
                    url: "{!! route('articles.timeHistogram.json', $article->id) !!}"
                }
            }
        })
    </script>

@endsection
