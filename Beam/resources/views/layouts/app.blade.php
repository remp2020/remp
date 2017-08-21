<?php

function route_active($routeName, $classes = '', $activeClasses = '')
{
    $currentRouteName = Route::currentRouteName();

    $currentRouteSegmentsCount = count(explode(".", $currentRouteName));
    $passedRouteSegmentsCount = count(explode(".", $routeName));

    if (strpos($currentRouteName, $routeName) === 0 && abs($currentRouteSegmentsCount-$passedRouteSegmentsCount) <= 1) {
        return "class=\"{$classes} active {$activeClasses}\"";
    }
    return "class=\"{$classes}\"";
}

?>

<!DOCTYPE html>
<!--[if IE 9 ]><html class="ie9"><![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title') </title>

    <link href="{{ asset(mix('/css/vendor.css', '/assets/vendor')) }}" rel="stylesheet">
    <link href="{{ asset(mix('/css/app.css', '/assets/vendor')) }}" rel="stylesheet">

    <script src="{{ asset(mix('/js/manifest.js', '/assets/vendor')) }}"></script>
    <script src="{{ asset(mix('/js/vendor.js', '/assets/vendor')) }}"></script>
    <script src="{{ asset(mix('/js/app.js', '/assets/vendor')) }}"></script>

    @stack('head')
</head>

<body data-ma-header="cyan-600">
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
        <li class="dropdown hidden-xs hidden-sm h-apps">
            <a data-toggle="dropdown" href="">
                <i class="hm-icon zmdi zmdi-apps"></i>
            </a>
            <ul class="dropdown-menu pull-right">
                <li>
                    <a href="">
                        <i class="palette-Green-400 bg zmdi zmdi-file-text"></i>
                        <small>Beam</small>
                    </a>
                </li>
                <li>
                    <a href="">
                        <i class="palette-Light-Blue bg zmdi zmdi-email"></i>
                        <small>Mailer</small>
                    </a>
                </li>
            </ul>
        </li>
        <li class="dropdown hm-profile">
            <a data-toggle="dropdown" href="">
                <img src="https://www.gravatar.com/avatar/{{ md5(Auth::user()->email) }}" alt="">
            </a>

            <ul class="dropdown-menu pull-right dm-icon">
                <li>
                    <a href=""><i class="zmdi zmdi-account"></i> View Profile</a>
                </li>
                <li>
                    <a href=""><i class="zmdi zmdi-settings"></i> Settings</a>
                </li>
                <li>
                    <a href=""><i class="zmdi zmdi-time-restore"></i> Logout</a>
                </li>
            </ul>
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
            <li {!! route_active('dashboard') !!}>
                <a href="{{ route('dashboard') }}"><i class="zmdi zmdi-home"></i> Dashboard</a>
            </li>
            <li {!! route_active('accounts') !!}>
                <a href="{{ route('accounts.index') }}" ><i class="zmdi zmdi-view-quilt"></i> Accounts</a>
            </li>
            <li {!! route_active('accounts.properties', 'sub-menu') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-email"></i> Properties</a>
                <ul>
                    @foreach (\App\Account::all() as $account)
                    <li><a href="{{ route("accounts.properties.index", $account->id) }}">{{ $account->name }}</a></li>
                    @endforeach
                </ul>
            </li>
            <li {!! route_active('segments') !!}>
                <a href="{{ route('segments.index') }}" ><i class="zmdi zmdi-view-quilt"></i> Segments</a>
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

<!-- Page Loader -->
<div class="page-loader palette-Cyan-600 bg">
    <div class="preloader pl-xl pls-white">
        <svg class="pl-circular" viewBox="25 25 50 50">
            <circle class="plc-path" cx="50" cy="50" r="20"/>
        </svg>
    </div>
</div>

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
        var index = 1000;
        @foreach ($errors->all() as $error)
        window.setTimeout(function() {
            $.bootstrapPurr( '{!! $error !!}' , {
                type: 'danger',
                align: 'left',
                allowDismiss: false,
                width: 270,
                offset: {
                    from: 'bottom'
                },
                delay: 10000
            });
        }, index);
        index += 250;
        @endforeach
    });
</script>

@stack('scripts')

</body>
</html>