<!DOCTYPE html>
@include('beam::layouts._head')

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

    <ul class="pull-right h-menu">
        <li class="hm-search-trigger">
            <a href="" data-ma-action="search-open">
                <i class="hm-icon zmdi zmdi-search"></i>
            </a>
        </li>
    </ul>

    <div class="media-body h-search site-search">
        <form class="p-relative">
            <div class="typeahead__container">
                <div class="typeahead__field">
                    <div class="preloader pl-lg pls-teal">
                        <svg class="pl-circular" viewBox="25 25 50 50">
                            <circle class="plc-path" cx="50" cy="50" r="20"></circle>
                        </svg>
                    </div>
                    <input class="js-typeahead hs-input typeahead"
                           name="q"
                           autocomplete="off"
                           placeholder="Search for articles (titles and IDs), authors, and segments">
                    <i class="zmdi zmdi-search hs-reset" data-ma-action="search-clear"></i>
                </div>
            </div>
        </form>
    </div>
    <div class="clearfix"></div>
</header>

<section id="main">
    <aside id="s-main-menu" class="sidebar">

        @if(isset($accountPropertyTokens))
            <form method="post" action="{{ route('properties.switch') }}">
                @csrf
                <select name="token"
                        class="token-select"
                        onchange="javascript:this.form.submit()">
                    @foreach($accountPropertyTokens as $account)
                        @if($account->name)
                            <optgroup label="{{$account->name}}">
                        @endif

                        @foreach($account->tokens as $token)
                            <option value="{{$token->uuid}}" {{$token->selected ? 'selected' : ''}}>{{$token->name}}</option>
                        @endforeach

                        @if($account->name)
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </form>
        @endif

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
                    @foreach (\Remp\BeamModule\Model\Account::all() as $account)
                    <li><a href="{{ route("accounts.properties.index", $account->id) }}">{{ $account->name }}</a></li>
                    @endforeach
                </ul>
            </li>
            <li class="m-b-15"></li>
            <li {!! route_active(['segments', 'entities', 'authorSegments.index', 'sectionSegments.index'], 'sub-menu', 'toggled') !!}>
                <a href="#" data-ma-action="submenu-toggle" ><i class="zmdi zmdi-accounts-list-alt"></i> Segments</a>
                <ul>
                    <li {!! route_active(['segments']) !!}>
                        <a href="{{ route('segments.index') }}" ><i class="zmdi zmdi-accounts-list m-r-5"></i> Segments</a>
                    </li>
                    <li {!! route_active(['authorSegments.index']) !!}>
                        <a href="{{ route('authorSegments.index') }}" ><i class="zmdi zmdi-accounts-list m-r-5"></i> Author segments</a>
                    </li>
                    <li {!! route_active(['sectionSegments.index']) !!}>
                        <a href="{{ route('sectionSegments.index') }}" ><i class="zmdi zmdi-accounts-list m-r-5"></i> Section segments</a>
                    </li>
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
            <li {!! route_active(['sections']) !!}>
                <a href="{{ route('sections.index') }}" ><i class="zmdi zmdi-collection-text"></i> Sections</a>
            </li>
            <li {!! route_active(['tags']) !!}>
                <a href="{{ route('tags.index') }}" ><i class="zmdi zmdi-label"></i> Tags</a>
            </li>
            <li {!! route_active(['tag-categories']) !!}>
                <a href="{{ route('tag-categories.index') }}" ><i class="zmdi zmdi-filter-list"></i> Tag Categories</a>
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

@include('beam::layouts._ie_warnings')

@include('beam::layouts._scripts')

@stack('scripts')

</body>
</html>
