@extends('layouts.app')

@section('title', 'Show article - ' . $article->title)

@section('content')

    <div class="c-header">
        <h2>{{ $article->title }}</h2>
    </div>

    <div class="well">
        <div class="row">
            <div class="col-md-6">
                <h4>Filter by date</h4>
                <div id="smart-range-selector">
                    {!! Form::hidden('data_from', $dataFrom) !!}
                    {!! Form::hidden('data_to', $dataTo) !!}
                    <smart-range-selector from="{{$dataFrom}}" to="{{$dataTo}}" :callback="callback">
                    </smart-range-selector>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h2>Show article <small>{{ $article->title }}</small></h2>
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
                    $('[name="data_from"]').val(from);
                    $('[name="data_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
