@extends('layouts.app')

@section('title', 'Conversions')

@section('content')

    <div class="c-header">
        <h2>User path</h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>User path statistics
                <small>Shows stats about actions users make prior conversion<br/></small>
            </h2>
        </div>

        <div class="card-body card-padding">
            <h5>Filter actions</h5>
            <div class="row">
                <div class="col-sm-2 m-b-25">
                    <p class="f-500 m-b-15 c-black">Actions done less than</p>

                    <select class="selectpicker bs-select-hidden">
                        @foreach($days as $day)
                            <option value="{{$day}}">{{$day}} {{str_plural('day', $day)}}</option>
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

                    <select class="selectpicker bs-select-hidden" title="No filter" data-live-search="true"  multiple="">
                        @foreach($sumCategories as $category)
                            <option value="{{$category->amount}}|{{$category->currency}}">{{$category->amount}} {{$category->currency}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-3 m-b-25">
                    <p class="f-500 m-b-15 c-black">Authors</p>

                    <select class="selectpicker bs-select-hidden" title="No filter" data-live-search="true"  multiple="">
                        @foreach($authors as $author)
                            <option value="{{$author->id}}">{{$author->name}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-3 m-b-25">
                    <p class="f-500 m-b-15 c-black">Sections</p>

                    <select class="selectpicker bs-select-hidden" title="No filter" data-live-search="true" multiple="">
                        @foreach($sections as $section)
                            <option value="{{$section->id}}">{{$section->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-info btn-sm m-t-10 waves-effect">Compute statistics</button>
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
                    $('[name="conversion_from"]').val(from);
                    $('[name="conversion_to"]').val(to).trigger("change");
                }
            }
        });
    </script>

@endsection
