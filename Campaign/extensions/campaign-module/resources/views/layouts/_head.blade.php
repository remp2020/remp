<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title') </title>

    <link rel="apple-touch-icon" sizes="57x57" href="/vendor/campaign/assets/img/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/vendor/campaign/assets/img/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/vendor/campaign/assets/img/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/vendor/campaign/assets/img/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/vendor/campaign/assets/img/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/vendor/campaign/assets/img/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/vendor/campaign/assets/img/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/vendor/campaign/assets/img/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/vendor/campaign/assets/img/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/vendor/campaign/assets/img/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/vendor/campaign/assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/vendor/campaign/assets/img/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/vendor/campaign/assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="/vendor/campaign/assets/img/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/vendor/campaign/assets/img/favicon/ms-icon-144x144.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset(mix('/css/vendor.css', '/vendor/campaign')) }}" rel="stylesheet">
    <link href="{{ asset(mix('/css/app.css', '/vendor/campaign')) }}" rel="stylesheet">

    <script src="{{ asset(mix('/js/manifest.js', '/vendor/campaign')) }}"></script>
    <script src="{{ asset(mix('/js/vendor.js', '/vendor/campaign')) }}"></script>
    <script src="{{ asset(mix('/js/app.js', '/vendor/campaign')) }}"></script>

    <script type="text/javascript">
        moment.locale('{{ Config::get('app.locale') }}');
    </script>

    {{-- tightenco/ziggy package to pass laravel routes to JS --}}
    @routes

    @stack('head')
</head>