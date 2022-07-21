<!DOCTYPE html>
@include('layouts._head')

<body data-ma-header="cyan-600">

<div class="remp-menu">
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="/">
                    <div class="svg-logo"></div>
                </a>
            </div>
            <ul class="nav navbar-nav navbar-remp display-on-computer">
                @foreach(config('services.remp.linked') as $key => $service)
                    @isset($service['url'])
                        <li @class(['active' => $service['url'] === '/'])>
                        <a href="{{ $service['url'] }}"><i class="zmdi zmdi-{{ $service['icon'] }} zmdi-hc-fw"></i> {{ $key }}</a>
                        </li>
                    @endisset
                @endforeach
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown hm-profile">
                    <a data-toggle="dropdown" href="">
                        <img src="https://www.gravatar.com/avatar/" alt="">
                    </a>

                    <ul class="dropdown-menu pull-right dm-icon">
                        <li>
                            <a href="{{ route('settings.jwtwhitelist') }}"><i class="zmdi zmdi-accounts-list"></i> JWT Whitelist</a>
                        </li>
                        <li>
                            <a href="{{ route('auth.logout-web') }}"><i class="zmdi zmdi-time-restore"></i> Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</div>

<header id="header" class="media">
    <div class="pull-left h-logo">
        <a href="/" class="hidden-xs"></a>

        <div class="menu-collapse" data-ma-action="sidebar-open" data-ma-target="main-menu">
            <div class="mc-wrap">
                <div class="mcw-line top palette-White bg"></div>
                <div class="mcw-line center palette-White bg"></div>
                <div class="mcw-line bottom palette-White bg"></div>
            </div>
        </div>
    </div>

    <ul class="pull-right h-menu">
        <li class="hm-search-trigger">
            <a href="" data-ma-action="search-open">
                <i class="hm-icon zmdi zmdi-search"></i>
            </a>
        </li>
    </ul>

    <div class="media-body h-search">
        <form class="p-relative">
            <input type="text" class="hs-input" placeholder="Search for people, files & reports">
            <i class="zmdi zmdi-search hs-reset" data-ma-action="search-clear"></i>
        </form>
    </div>

</header>

<section id="main">
    <aside id="s-main-menu" class="sidebar">
        <ul class="main-menu">
            <li {!! route_active(['api-tokens.index']) !!}>
                <a href="{{ route('api-tokens.index') }}"><i class="zmdi zmdi-key"></i> API Tokens</a>
            </li>
        </ul>
    </aside>

    <section id="content">
        <div class="container">
            @yield('content')

            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    <footer id="footer">
        <p>Thank you for using <a href="https://remp2020.com/" title="Readers’ Engagement and Monetization Platform | Open-source tools for publishers">REMP</a>, open-source software by Denník N.</p>
    </footer>
</section>


<!-- Older IE warning message -->
<!--[if lt IE 9]>
<div class="ie-warning">
    <h1 class="c-white">Warning!!</h1>
    <p>You are using an outdated version of Internet Explorer, please upgrade <br/>to any of the following web browsers to access this website.</p>
    <div class="iew-container">
        <ul class="iew-download">
            <li>
                <a href="http://www.google.com/chrome/">
                    <img src="img/browsers/chrome.png" alt="">
                    <div>Chrome</div>
                </a>
            </li>
            <li>
                <a href="https://www.mozilla.org/en-US/firefox/new/">
                    <img src="img/browsers/firefox.png" alt="">
                    <div>Firefox</div>
                </a>
            </li>
            <li>
                <a href="http://www.opera.com">
                    <img src="img/browsers/opera.png" alt="">
                    <div>Opera</div>
                </a>
            </li>
            <li>
                <a href="https://www.apple.com/safari/">
                    <img src="img/browsers/safari.png" alt="">
                    <div>Safari</div>
                </a>
            </li>
            <li>
                <a href="http://windows.microsoft.com/en-us/internet-explorer/download-ie">
                    <img src="img/browsers/ie.png" alt="">
                    <div>IE (New)</div>
                </a>
            </li>
        </ul>
    </div>
    <p>Sorry for the inconvenience!</p>
</div>
<![endif]-->

<script type="application/javascript">
    $(document).ready(function() {
        let delay = 250;
        @foreach ($errors->all() as $error)
        (function(delay) {
            window.setTimeout(function() {
                $.notify({
                    message: '{{ $error }}'
                }, {
                    allow_dismiss: false,
                    type: 'danger'
                });
            }, delay);
        })(delay);
        delay += 250;
        @endforeach
    });
</script>

@stack('scripts')

</body>
</html>
