@extends('beam::layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="c-header">
        <h2>Settings</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col">
                    <ul id="myTab" class="tab-nav tn-justified" role="tablist">
                        @foreach($configsByCategories as $category => $configs)
                            <li role="presentation" class="{{$loop->index === 0 ? 'active' : ''}}">
                                <a class="col-sx-4" href="#{{$configs[0]->configCategory->code}}" aria-controls="{{$configs[0]->configCategory->code}}" role="tab" data-toggle="tab" aria-expanded="true">
                                    {{$category}}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')
            <div class="row">
                <div class="tab-content p-20">
                    @foreach($configsByCategories as $category => $configs)
                        <div role="tabpanel" class="tab-pane animated fadeIn {{$loop->index === 0 ? 'active' : ''}}" id="{{$configs[0]->configCategory->code}}">
                            <form method="post" action="{{ route('settings.update', ['configCategory' => $configs[0]->configCategory, 'redirect_url' => Remp\BeamModule\Model\Config\ConfigCategory::getSettingsTabUrl($configs[0]->configCategory->code)]) }}">
                                {{ csrf_field() }}

                                @if($configs[0]->configCategory->code === Remp\BeamModule\Model\Config\ConfigCategory::CODE_AUTHOR_SEGMENTS)
                                    <div class="well col">
                                        <p><i class="zmdi zmdi-info"></i> Before you configure author segments, you can test the parameters at the configuration testing page. When you are satisfied with the resulting segments, you can get back here and configure the final parameters for calculation.</p>
                                        <a href="{{ route('authorSegments.testingConfiguration') }}" id="author-testing-link" class="btn btn-info waves-effect">Test Configuration</a>
                                    </div>
                                @elseif($configs[0]->configCategory->code === Remp\BeamModule\Model\Config\ConfigCategory::CODE_SECTION_SEGMENTS)
                                    <div class="well col">
                                        <p><i class="zmdi zmdi-info"></i> Before you configure section segments, you can test the parameters at the configuration testing page. When you are satisfied with the resulting segments, you can get back here and configure the final parameters for calculation.</p>
                                        <a href="{{ route('sectionSegments.testingConfiguration') }}" id="section-testing-link" class="btn btn-info waves-effect">Test Configuration</a>
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    @foreach($configs as $config)
                                        <p class=" f-500 m-b-10"><span class="c-black">{{ $config->display_name ?? $config->name}}</span>
                                            <small>
                                                @if($config->description)
                                                    <br />{{$config->description}}
                                                @endif
                                            </small>
                                        </p>

                                        <div class="form-group">
                                            <div class="fg-line">
                                                <input type="text" name="settings[{{$config->name}}]"  value="{{old('settings.' . $config->name, $config->value)}}" class="form-control fg-input">
                                            </div>
                                        </div>
                                    @endforeach
                                    <button type="submit" name="save" value="Save" class="btn btn-info waves-effect">
                                        <i class="zmdi zmdi-mail-send"></i> Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(() => {
            let url = location.href.replace(/\/$/, "");

            if (location.hash) {
                const hash = url.split("#");
                $('#myTab a[href="#'+hash[1]+'"]').tab("show");
                setTimeout(() => {
                    $(window).scrollTop(0);
                }, 400);
            }

            $('a[data-toggle="tab"]').on("click", function() {
                let newUrl;
                const hash = $(this).attr("href");
                newUrl = url.split("#")[0] + hash;
                history.replaceState(null, null, newUrl);
            });
        });
    </script>
@endpush
