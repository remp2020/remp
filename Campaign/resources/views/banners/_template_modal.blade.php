<div class="modal" id="modal-template-select" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Select template</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_MEDIUM_RECTANGLE]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Medium rectangle</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview medium-rectangle"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_BAR]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Bar</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview bar"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_COLLAPSIBLE_BAR]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Collapsible bar</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview collapsible-bar"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_SHORT_MESSAGE]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Short message</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview short-message"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_OVERLAY_RECTANGLE]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Overlay rectangle banner</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview overlay-rectangle"></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_HTML]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">Custom HTML</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview" style="margin-top: 55px;">
                                        <i class="zmdi zmdi-language-html5 zmdi-hc-5x"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <a href="{{ route('banners.create', ['template' => \App\Banner::TEMPLATE_HTML_OVERLAY]) }}">
                                <div class="card-header">
                                    <h4 class="text-center">HTML overlay</h4>
                                </div>
                                <div class="card-body">
                                    <div class="preview" style="margin-top: 55px;">
                                        <i class="zmdi zmdi-language-html5 zmdi-hc-5x"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
