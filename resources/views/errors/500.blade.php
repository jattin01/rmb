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
        <link href="{{ asset('assets/css/fontawesome-free/css/flasher.css') }}" rel="stylesheet">
    </head>
    <body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
        <div class="wrapper">
        <div class="content-wrapper" style = "margin-left:0% !important;">
            <section class="content">
                <div class="container-fluid">
                    <div class="px-sm-4">
                        <div class="row">
                            <div class="col-md-12 text-center mt-4">
                                <img src = "{{asset('assets/img/cancelled-imgpop.svg')}}" />
                                <h6 style = "margin : 1rem;">
                                <h4 class = "text-danger mb-2">500 Server Error</h4>
                                Oops ! Something went wrong on our end. Please try again later.
                                </h6>
                                <br/>
                                <a href="{{route('dashboard.index')}}" class="btn back-btn mt-3">Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        </div>
        <script>
            console.log("Internal Server Error - " + "{{isset($exception) ? $exception->getMessage() : ''}}")
        </script>
    </body>
</html>