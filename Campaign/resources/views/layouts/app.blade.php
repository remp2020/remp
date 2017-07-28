
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

    <!-- Vendor CSS -->
    <link href="/assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="/assets/vendor/material-design-iconic-font/dist/css/material-design-iconic-font.min.css" rel="stylesheet">
    <link href="/assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" rel="stylesheet">
    <link href="/assets/vendor/google-material-color/dist/palette.css" rel="stylesheet">
    <link href="/assets/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- CSS -->
    <link href="/assets/css/app.min.1.css" rel="stylesheet">
    <link href="/assets/css/app.min.2.css" rel="stylesheet">

    <script src="/assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="/assets/vendor/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.3.4/vue.js" integrity="sha256-sawP1sLkcaA4YQJQWAtjahamgG6brGmaIJWRhYwDfno=" crossorigin="anonymous"></script>
    <script src="/assets/vendor/sweetalert2/dist/sweetalert2.min.js"></script>

    @stack('head')

    <link href="/assets/css/remp.css" rel="stylesheet">
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
        <li class="hm-alerts" data-user-alert="sua-messages" data-ma-action="sidebar-open" data-ma-target="user-alerts">
            <a href=""><i class="hm-icon zmdi zmdi-notifications"></i></a>
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
            <input type="text" class="hs-input" placeholder="Search for banners and campaigns">
            <i class="zmdi zmdi-search hs-reset" data-ma-action="search-clear"></i>
        </form>
    </div>

</header>

<section id="main">
    <aside id="s-user-alerts" class="sidebar">
        <div class="card">
            <div class="card-header ch-img" style="background:white; height: 200px;">
                <button data-ma-action="sidebar-close" class="btn palette-Red-600 bg btn-float waves-effect waves-circle waves-float"><i class="zmdi zmdi-arrow-left"></i></button>
            </div>
            <div class="card-header">
                <h2>
                    HELP
                    <small>Lorem ipsum dolor sit amet</small>
                </h2>
            </div>
            <div class="card-body card-padding">
                <p>Donec ullamcorper nulla non metus auctor fringilla. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Vestibulum id ligula porta felis euismod semper. Nulla vitae elit libero, a pharetra </p>
            </div>
        </div>
    </aside>

    <aside id="s-main-menu" class="sidebar">
        <div class="smm-header">
            <i class="zmdi zmdi-long-arrow-left" data-ma-action="sidebar-close"></i>
        </div>

        <ul class="smm-alerts">
            <li data-ma-action="sidebar-open" data-ma-target="user-alerts">
                <i class="zmdi zmdi-help"></i>
            </li>
        </ul>

        <ul class="main-menu">
            <li {!! route_active('dashboard') !!}>
                <a href="{{ route('dashboard') }}"><i class="zmdi zmdi-home"></i> Dashboard</a>
            </li>
            <li {!! route_active('banners') !!}>
                <a href="{{ route('banners.index') }}" ><i class="zmdi zmdi-view-quilt"></i> Banners</a>
            </li>
            <li {!! route_active('campaigns') !!}>
                <a href="{{ route('campaigns.index') }}" ><i class="zmdi zmdi-view-quilt"></i> Campaigns</a>
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

<!-- Javascript Libraries -->
<script src="/assets/vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
<script src="/assets/vendor/waves/dist/waves.min.js"></script>
<script src="/assets/vendor/jquery-bootstrap-purr/jquery-bootstrap-purr.min.js"></script>
<script src="/assets/vendor/autosize/dist/autosize.min.js"></script>
<script src="/assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>

<!-- Placeholder for IE9 -->
<!--[if IE 9 ]>
<script src="/assets/vendor/jquery-placeholder/jquery.placeholder.min.js"></script>
<![endif]-->

<script src="/assets/js/functions.js"></script>
<script src="/assets/js/actions.js"></script>
<script src="/assets/js/datatables.js"></script>

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