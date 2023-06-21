<!DOCTYPE html>
@include('beam::layouts._head')

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
        <p>Thank you for using <a href="https://remp2020.com/" title="Readers’ Engagement and Monetization Platform | Open-source tools for publishers">REMP</a>, open-source software by Denník N.</p>
    </footer>
</section>

@include('beam::layouts._ie_warnings')

@include('beam::layouts._scripts')

@stack('scripts')

</body>
</html>
