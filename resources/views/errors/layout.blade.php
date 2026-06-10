<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="{{ asset('favicon.ico') }}" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1440.css') }}" media="(max-width: 1440px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1190.css') }}" media="(max-width: 1190px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-960.css') }}" media="(max-width: 960px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-768.css') }}" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-640.css') }}" media="(max-width: 640px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-480.css') }}" media="(max-width: 480px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-390.css') }}" media="(max-width: 390px)">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800&subset=latin,cyrillic" rel="stylesheet" type="text/css">
</head>
<body style="background-color: #2A2D44 !important;">
    <section class="section-for-header">
        <div class="container header-entrance">
            <div class="row">
                <div class="col-xs-12">
                    <a href="{{ url('/') }}" class="entrance-logo">
                        <img border="0" src="{{ asset('frontend/images/logo-main.png') }}" alt="logo">
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="photo-caption"></div>

    <div id="background">
        <h1 style="text-align:center;font-size:70px;">@yield('code')</h1>
        <p style="text-align:center;font-size:30px;">@yield('message')</p>
    </div>

    <script src="{{ asset('frontend/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery-migrate-3.5.2.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('frontend/js/bootstrap.min.js') }}"></script>
</body>
</html>
