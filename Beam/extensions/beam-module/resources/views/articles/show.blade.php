@extends('beam::layouts.app')

@section('title', 'Show article - ' . $article->title)

@section('content')

    <div class="c-header">
        <h2>Article Details</h2>
    </div>

    <div id="article-vue-wrapper">
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
                    <date-formatter date="{{$article->published_at}}"></date-formatter>
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
                                <dt>Content Type</dt>
                                <dd>{{$article->content_type}}</dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Property</dt>
                                <dd><a href="{{ route('accounts.properties.index', $article->property->account->id) }}">{{$article->property->name}}</a></dd>
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
                                    @foreach ($article->authors as $author)
                                        <a href="{{ route('authors.show', $author->id) }}">{{ $author->name }}</a>@if(!$loop->last), @endif
                                    @endforeach
                                </dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Sections</dt>
                                <dd>
                                    @if($article->sections->count() > 0)
                                        @foreach ($article->sections as $section)
                                            {{ $section->name }}@if(!$loop->last), @endif
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Tags</dt>
                                <dd>
                                    @if($article->tags->count() > 0)
                                        @foreach ($article->tags as $tag)
                                            {{ $tag->name }}@if(!$loop->last), @endif
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Published at</dt>
                                <dd>
                                    <date-formatter date="{{$article->published_at}}"></date-formatter>
                                </dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Created at</dt>
                                <dd>
                                    <date-formatter date="{{$article->created_at}}"></date-formatter>
                                </dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Updated at</dt>
                                <dd>
                                    <date-formatter date="{{$article->updated_at}}"></date-formatter>
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
                                <dd>{{$article->conversion_rate}}</dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>New conversions</dt>
                                <dd>{{$article->new_conversions_count}}</dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt><span data-toggle="tooltip" data-placement="top" title="" data-original-title="Users who already had a subscription in the past">Renewed conversions</span></dt>
                                <dd>{{$article->renewed_conversions_count}}</dd>
                            </dl>
                            <dl class="dl-horizontal">
                                <dt>Conversions amount</dt>
                                <dd>{{ $conversionsSums }}</dd>
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

                <div class="pmb-block">
                    <div class="pmbb-header">
                        <h2><i class="zmdi zmdi-time m-r-5"></i> Time spent</h2>
                    </div>
                    <div class="pmbb-body p-l-30">
                        <div class="pmbb-view">
                            <dl class="dl-horizontal">
                                <dt>Avg time subscribers</dt>
                                <dd>{{ $averageTimeSpentSubscribers }}</dd>
                            </dl>

                            <dl class="dl-horizontal">
                                <dt>Avg time signed-in</dt>
                                <dd>{{ $averageTimeSpentSignedId }}</dd>
                            </dl>

                            <dl class="dl-horizontal">
                                <dt>Avg time all</dt>
                                <dd>{{ $averageTimeSpentAll }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                @widgetGroup('article.show.info')

            </div>
        </div>

        <article-details
                :has-title-variants="{{$article->has_title_variants ? 'true' : 'false'}}"
                :has-image-variants="{{$article->has_image_variants ? 'true' : 'false'}}"
                :url="url"
                :variants-url="variantsUrl"
                :default-graph-data-source="defaultGraphDataSource"
                :external-events="externalEvents"
                ref="histogram">
        </article-details>

    </div>
    <script type="text/javascript">


        new Vue({
            el: "#article-vue-wrapper",
            components: {
                ArticleDetails, DateFormatter
            },
            created: function() {
                document.addEventListener('visibilitychange', this.visibilityChanged)
            },
            beforeDestroy: function() {
                document.removeEventListener('visibilitychange', this.visibilityChanged)
            },
            data: function() {
                return {
                    url: "{!! route('articles.timeHistogram.json', $article->id) !!}",
                    variantsUrl: "{!! route('articles.variantsHistogram.json', $article->id) !!}",
                    externalEvents: {!! @json($externalEvents) !!},
                    defaultGraphDataSource: "{!! config('beam.article_traffic_graph_data_source') !!}",
                }
            }
        })
    </script>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Referer stats <small></small></h2>
                    <div id="smart-range-selector">
                        {{ html()->hidden('visited_from', $visitedFrom) }}
                        {{ html()->hidden('visited_to', $visitedTo) }}
                        <smart-range-selector from="{{$visitedFrom}}" to="{{$visitedTo}}" :callback="callback">
                        </smart-range-selector>
                    </div>
                </div>

                <script>
                    $.fn.dataTables['render']['referer_medium'] = function () {
                        return function(data) {
                            var colors = {!! @json($mediumColors) !!};
                            return "<span style='font-size: 18px; color:" + colors[data] + "'>●</span> " + data;
                        }
                    };
                </script>

                {!! Widget::run('DataTable', [
                    'paging' => [[10,30,100], 30],
                    'colSettings' => [
                        'derived_referer_medium' => [
                            'header' => 'medium',
                            'orderable' => false,
                            'filter' => $mediums,
                            'priority' => 1,
                            'render' => 'referer_medium',
                        ],
                        'source' => [
                            'header' => 'source',
                            'searchable' => false,
                            'orderable' => false,
                            'priority' => 1,
                        ],
                        'visits_count' => [
                            'header' => 'visits count',
                            'searchable' => false,
                            'priority' => 1,
                            'orderSequence' => ['desc', 'asc'],
                            'render' => 'number',
                            'className' => 'text-right',
                        ],
                        'first_conversion_source_count' => [
                            'header' => 'first conversion source count',
                            'searchable' => false,
                            'priority' => 2,
                            'orderSequence' => ['desc', 'asc'],
                            'render' => 'number',
                            'className' => 'text-right',
                        ],
                        'last_conversion_source_count' => [
                            'header' => 'last conversion source count',
                            'searchable' => false,
                            'priority' => 2,
                            'orderSequence' => ['desc', 'asc'],
                            'render' => 'number',
                            'className' => 'text-right',
                        ],
                    ],
                    'dataSource' => route('articles.dtReferers', $article->id),
                    'order' => [2, 'desc'],
                    'requestParams' => [
                        'visited_from' => '$(\'[name="visited_from"]\').val()',
                        'visited_to' => '$(\'[name="visited_to"]\').val()',
                        'tz' => 'Intl.DateTimeFormat().resolvedOptions().timeZone',
                    ],
                    'refreshTriggers' => [
                        [
                            'event' => 'change',
                            'selector' => '[name="visited_from"]'
                        ],
                        [
                            'event' => 'change',
                            'selector' => '[name="visited_to"]',
                        ]
                    ],
                ]) !!}
            </div>
        </div>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#smart-range-selector",
            components: {
                SmartRangeSelector
            },
            methods: {
                callback: function (from, to) {
                    $('[name="visited_from"]').val(from);
                    $('[name="visited_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
