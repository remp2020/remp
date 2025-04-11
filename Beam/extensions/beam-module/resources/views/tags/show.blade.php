@extends('beam::layouts.app')

@section('title', 'Show tag - ' . $tag->name)

@section('content')

    <div class="c-header">
        <h2>{{ $tag->name }}</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by publish date</h4>
                <div id="smart-range-selector">
                    {{ html()->hidden('published_from', $publishedFrom) }}
                    {{ html()->hidden('published_to', $publishedTo) }}
                    <smart-range-selector from="{{$publishedFrom}}" to="{{$publishedTo}}" :callback="callback">
                    </smart-range-selector>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show tag <small>{{ $tag->name }}</small></h2>
            </div>

            @include('beam::articles.subviews.dt_articles', ['dataSource' => route('tags.dtArticles', $tag)])

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
                    $('[name="published_from"]').val(from);
                    $('[name="published_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
