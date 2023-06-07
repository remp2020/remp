@extends('beam::layouts.app')

@section('title', 'Conversions')

@section('content')

    <div class="c-header">
        <h2>User path</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Configuration
                <small>Compute statistics about actions users make prior conversion<br/></small>
            </h2>
        </div>

        <div id="userpath-vue" class="card-body card-padding">
            <form method="post" v-on:submit.prevent="sendForm">
                <h5>Filter actions</h5>
                <div class="row">
                    <div class="col-sm-2 m-b-25">
                        <p class="f-500 m-b-15 c-black">Actions done less than</p>

                        <select v-model="form.days" name="days" class="selectpicker bs-select-hidden">
                            @foreach($days as $day)
                                <option value="{{$day}}">{{$day}} {{Str::plural('day', $day)}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3 m-b-25">
                        <p class="c-black" style="margin-top: 40px;">prior conversions</p>
                    </div>
                </div>

                <h5>Filter conversions</h5>
                <div class="row">
                    <div class="col-sm-3 m-b-25">
                        <p class="f-500 m-b-15 c-black">Conversion amount</p>

                        <select v-model="form.sums" name="sums[]" class="selectpicker bs-select-hidden" title="No filter" data-live-search="true"  multiple="">
                            @foreach($sumCategories as $category)
                                <option value="{{$category->amount}}|{{$category->currency}}">{{$category->amount}} {{$category->currency}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-3 m-b-25">
                        <p class="f-500 m-b-15 c-black">Authors</p>

                        <select v-model="form.authors" name="authors[]" class="selectpicker bs-select-hidden" title="No filter" data-live-search="true" data-live-search-normalize="true" multiple="">
                            @foreach($authors as $author)
                                <option value="{{$author->id}}">{{$author->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-3 m-b-25">
                        <p class="f-500 m-b-15 c-black">Sections</p>

                        <select v-model="form.sections" name="sections[]" class="selectpicker bs-select-hidden" title="No filter" data-live-search="true" data-live-search-normalize="true" multiple="">
                            @foreach($sections as $section)
                                <option value="{{$section->id}}">{{$section->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-info btn-sm m-t-10 waves-effect">Compute statistics</button>
            </form>

            <user-path :stats="stats" :loading="loading" :error="error"></user-path>
        </div>
        <div id="conversions-diagram-vue" class="card-body card-padding">
            <conversions-sankey-diagram v-for="conversionSourceType in conversionSourceTypes" :data-url="dataUrl" :conversion-source-type="conversionSourceType"></conversions-sankey-diagram>
        </div>
    </div>

    <script type="text/javascript">
        new Vue({
            el: "#userpath-vue",
            components: {
                UserPath
            },
            data: {
                url: "{!! route('userpath.stats') !!}",
                form: {
                    days: 2,
                    sums: [],
                    authors: [],
                    sections: [],
                },
                stats: null,
                loading: false,
                error: null,
            },
            methods: {
                sendForm: function () {
                    this.loading = true
                    var that = this;
                    $.post(this.url, this.form, function(data) {
                        that.stats = data;
                        that.loading = false;
                    }, 'json').fail(function() {
                        let errorMsg = 'Error while loading statistics data, try again later please.'
                        that.error = errorMsg
                        console.warn(that.error);

                        $.notify({
                            message: errorMsg
                        }, {
                            allow_dismiss: false,
                            type: 'danger'
                        });

                        that.loading = false;
                    });
                }
            }
        });

        new Vue({
            el: "#conversions-diagram-vue",
            components: {
                ConversionsSankeyDiagram
            },
            data: {
                dataUrl: "{!! route('userpath.diagramData') !!}",
                conversionSourceTypes: {!! json_encode($conversionSourceTypes) !!}
            }
        });
    </script>

@endsection
