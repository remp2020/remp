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
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

                    <ul class="dropdown-menu pull-right dm-icon">
                        <li>
                            <a href="{{ route('auth.logout') }}"><i class="zmdi zmdi-time-restore"></i> Logout</a>
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

    <div class="media-body h-search">
        <form class="p-relative">
            <input type="text" class="hs-input" placeholder="Search for banners and campaigns">
            <i class="zmdi zmdi-search hs-reset" data-ma-action="search-clear"></i>
        </form>
    </div>

</header>

<section id="main">
    <aside id="s-main-menu" class="sidebar">
        <ul class="main-menu">
            <li {!! route_active(['banners']) !!}>
                <a href="{{ route('banners.index') }}" ><i class="zmdi zmdi-collection-folder-image"></i> Banners</a>
            </li>
            <li {!! route_active(['campaigns']) !!}>
                <a href="{{ route('campaigns.index') }}" ><i class="zmdi zmdi-ticket-star"></i> Campaigns</a>
            </li>
        </ul>
    </aside>

    <section id="content">
        <div class="container">
            @yield('content')

            @if (count($errors) > 0)
                <div class="alert alert-danger m-t-30">
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
        var delay = 250;
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
        @endforeach
        @if (session('warning'))
            $.notify({
                message: '{{ session('warning') }}'
            }, {
                allow_dismiss: false,
                type: 'warning',
                placement: {
                    from: "bottom",
                    align: "left"
                }
            });
        @endif
        @if (session('success'))
        $.notify({
            message: '{{ session('success') }}'
        }, {
            allow_dismiss: false,
            type: 'info',
            placement: {
                from: "bottom",
                align: "left"
            }
        });
        @endif
    });
</script>

@stack('scripts')

</body>
</html>
