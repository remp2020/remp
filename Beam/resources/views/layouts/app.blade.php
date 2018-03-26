<!DOCTYPE html>
<!--[if IE 9 ]><html class="ie9"><![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title') </title>

    <link rel="apple-touch-icon" sizes="57x57" href="/assets/img/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/assets/img/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/img/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/assets/img/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/img/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/assets/img/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/assets/img/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/img/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/assets/img/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="/assets/img/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/assets/img/favicon/ms-icon-144x144.png">

    <link href="{{ asset(mix('/css/vendor.css', '/assets/vendor')) }}" rel="stylesheet">
    <link href="{{ asset(mix('/css/app.css', '/assets/vendor')) }}" rel="stylesheet">

    <script src="{{ asset(mix('/js/manifest.js', '/assets/vendor')) }}"></script>
    <script src="{{ asset(mix('/js/vendor.js', '/assets/vendor')) }}"></script>
    <script src="{{ asset(mix('/js/app.js', '/assets/vendor')) }}"></script>

    <script type="text/javascript">
        moment.locale('{{ Config::get('app.locale') }}');
    </script>

    @stack('head')
</head>

<body data-ma-header="cyan-600">

<div class="remp-menu">
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="/">
                    <div class="svg-logo"></div>
                </a>
            </div>
            <ul class="nav navbar-nav navbar-remp">
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
                        <img src="https://www.gravatar.com/avatar/{{ md5(Auth::user()->email) }}" alt="">
                    </a>

                    {{--<ul class="dropdown-menu pull-right dm-icon">--}}
                        {{--<li>--}}
                            {{--<a href="#"><i class="zmdi zmdi-account"></i> View Profile</a>--}}
                        {{--</li>--}}
                        {{--<li>--}}
                            {{--<a href="#"><i class="zmdi zmdi-settings"></i> Settings</a>--}}
                        {{--</li>--}}
                        {{--<li>--}}
                            {{--<a href="#"><i class="zmdi zmdi-time-restore"></i> Logout</a>*}--}}
                        {{--</li>--}}
                    {{--</ul>--}}
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
            <li {!! route_active(['accounts']) !!}>
                <a href="{{ route('accounts.index') }}" ><i class="zmdi zmdi-cloud-box"></i> Accounts</a>
            </li>
            <li {!! route_active(['accounts.properties'], 'sub-menu') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-view-quilt"></i> Properties</a>
                <ul>
                    @foreach (\App\Account::all() as $account)
                    <li><a href="{{ route("accounts.properties.index", $account->id) }}">{{ $account->name }}</a></li>
                    @endforeach
                </ul>
            </li>
            <li class="m-b-15"></li>
            <li {!! route_active(['segments']) !!}>
                <a href="{{ route('segments.index') }}" ><i class="zmdi zmdi-accounts-list-alt"></i> Segments</a>
            </li>
            <li class="m-b-15"></li>
            <li {!! route_active(['articles.conversions', 'articles.pageviews'], 'sub-menu', 'toggled') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-library"></i> Articles</a>
                <ul>
                    <li {!! route_active(['articles.conversions']) !!}>
                        <a href="{{ route('articles.conversions') }}" ><i class="zmdi zmdi-chart"></i> Conversion stats</a>
                    </li>
                    <li {!! route_active(['articles.pageviews']) !!}>
                        <a href="{{ route('articles.pageviews') }}" ><i class="zmdi zmdi-chart"></i> Pageview stats</a>
                    </li>
                </ul>
            </li>
            <li {!! route_active(['conversions']) !!}>
                <a href="{{ route('conversions.index') }}" ><i class="zmdi zmdi-money-box"></i> Conversions</a>
            </li>
            <li {!! route_active(['authors']) !!}>
                <a href="{{ route('authors.index') }}" ><i class="zmdi zmdi-account-box"></i> Authors</a>
            </li>
            <li {!! route_active(['visitors.devices'], 'sub-menu', 'toggled') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-face"></i> Visitors</a>
                <ul>
                    <li {!! route_active(['visitors.devices']) !!}>
                        <a href="{{ route('visitors.devices') }}" ><i class="zmdi zmdi-smartphone"></i> Devices</a>
                    </li>
                    <li {!! route_active(['visitors.sources']) !!}>
                        <a href="{{ route('visitors.sources') }}" ><i class="zmdi zmdi-shape"></i> Sources</a>
                    </li>
                </ul>
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
        Copyright &copy; 2017 REMP
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
