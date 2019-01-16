<!DOCTYPE html>
<!--[if IE 9 ]><html class="ie9"><![endif]-->
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
                            <a href="{{ route('settings.index') }}"><i class="zmdi zmdi-settings"></i> Settings</a>
                        </li>
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
            <input type="text" class="hs-input" placeholder="Search for people, files & reports">
            <i class="zmdi zmdi-search hs-reset" data-ma-action="search-clear"></i>
        </form>
    </div>

</header>

<section id="main">
    <aside id="s-main-menu" class="sidebar">
        <ul class="main-menu">
            <li {!! route_active(['dashboard']) !!}>
                <a href="{{ route('dashboard.index') }}" ><i class="zmdi zmdi-chart"></i> Dashboard</a>
            </li>
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
            <li {!! route_active(['segments', 'entities'], 'sub-menu', 'toggled') !!}>
                <a href="{{ route('segments.index') }}" ><i class="zmdi zmdi-accounts-list-alt"></i> Segments</a>
                <ul>
                    <li {!! route_active(['entities']) !!}>
                        <a href="{{ route('entities.index') }}" ><i class="zmdi zmdi-crop-free m-r-5"></i> Entities</a>
                    </li>
                </ul>
            </li>
            <li class="m-b-15"></li>
            <li {!! route_active(['articles.conversions', 'articles.pageviews'], 'sub-menu', 'toggled') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-library"></i> Articles</a>
                <ul>
                    <li {!! route_active(['articles.conversions']) !!}>
                        <a href="{{ route('articles.conversions') }}" ><i class="zmdi zmdi-chart m-r-5"></i> Conversion stats</a>
                    </li>
                    <li {!! route_active(['articles.pageviews']) !!}>
                        <a href="{{ route('articles.pageviews') }}" ><i class="zmdi zmdi-chart m-r-5"></i> Pageview stats</a>
                    </li>
                </ul>
            </li>
            @if (config('services.remp.mailer.api_token'))
            <li {!! route_active(['newsletters']) !!}>
                <a href="{{ route('newsletters.index') }}" ><i class="zmdi zmdi-email"></i> Newsletters</a>
            </li>
            @endif
            <li {!! route_active(['conversions', 'userpath'], 'sub-menu', 'toggled') !!}>
                <a href="#" data-ma-action="submenu-toggle"><i class="zmdi zmdi-face"></i> Conversions</a>
                <ul>
                    <li {!! route_active(['conversions']) !!}>
                        <a href="{{ route('conversions.index') }}" ><i class="zmdi zmdi-money-box"></i> Conversions</a>
                    </li>
                    <li {!! route_active(['userpath']) !!}>
                        <a href="{{ route('userpath.index') }}" ><i class="zmdi zmdi-arrow-split"></i> User path</a>
                    </li>
                </ul>
            </li>
            <li {!! route_active(['authors']) !!}>
                <a href="{{ route('authors.index') }}" ><i class="zmdi zmdi-account-box"></i> Authors</a>
            </li>
            <li {!! route_active(['visitors.devices', 'visitors.sources'], 'sub-menu', 'toggled') !!}>
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
            @if (config('google.ga_reporting_enabled'))
            <li {!! route_active(['googleanalyticsreporting']) !!}>
                <a href="{{ route('googleanalyticsreporting.index') }}" ><i class="zmdi zmdi-chart-donut"></i> GA Reporting</a>
            </li>
            @endif
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
        Copyright &copy; 2017 - {{ date('Y') }} REMP
    </footer>
</section>

@include('layouts._ie_warnings')

@include('layouts._scripts')

@stack('scripts')

</body>
</html>
