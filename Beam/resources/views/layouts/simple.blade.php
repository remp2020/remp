<!DOCTYPE html>
<!--[if IE 9 ]><html class="ie9"><![endif]-->
@include('layouts._head')

<body data-ma-header="cyan-600">

<section id="main-simple">
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
