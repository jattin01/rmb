<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name', 'Ant') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link href="{{ asset('assets/css/font-awesome.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/responsive.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/font-awesome.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <link href="{{ asset('assets/css/fontawesome-free/css/flasher.css') }}" rel="stylesheet">
    <script href="{{asset('assets/js/flasher-js.min.js')}}"></script>

</head>

<body class="login-body">
    <div class="wrapper">

        <!-- BEGIN: Content-->
        @yield('content')
        <!-- END: Content-->

    </div>

    <!-- BEGIN: Script-->
    @yield('scripts')
    <!-- END: Script-->
</body>

</html>
