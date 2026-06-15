<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="Мы первый спортивный интернет-ресурс, объединивший приверженцев здорового образа жизни, любителей спорта и профессиональных спортсменов.">
    <title>{{ $title ?? 'PlayToGet' }}</title>
    <link href="{{ asset('favicon.ico') }}" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}?v=2026061520">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1440.css') }}" media="(max-width: 1440px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1190.css') }}" media="(max-width: 1190px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-960.css') }}?v=2026061404" media="(max-width: 960px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-768.css') }}" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-640.css') }}" media="(max-width: 640px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-480.css') }}" media="(max-width: 480px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-390.css') }}" media="(max-width: 390px)">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800&subset=latin,cyrillic" rel="stylesheet" type="text/css">
    <script src="{{ asset('frontend/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('frontend/js/main_page.js') }}"></script>
</head>
<body class="index-registration-body rel">
<div class="wrapper-backgrounded">
    <div class="wrapper-transparent">
        <section class="section-for-main-entrance main_top">
            <div class="container main-entrance">
                <div class="row">
                    <div class="col-md-7 section-sport-inside margin_none">
                        <div class="container header-entrance margin_none">
                            <div class="row">
                                <div class="col-xs-12">
                                    <a href="{{ route('front.home') }}" class="entrance-logo">
                                        <img src="{{ asset('frontend/images/playtoget-logo-clean.svg') }}?v=2026061404" width="290" height="86" alt="playtoget-logo">
                                    </a>
                                    <div class="col-md-6 col-md-offset-3"><hr></div>
                                    <div class="col-md-8 col-md-offset-2">
                                        <h1>Спортивный интернет-проект</h1>
                                        <p class="desc">Мы первый спортивный интернет-ресурс, объединивший:<br>
                                            приверженцев здорового образа жизни, любителей спорта и профессиональных спортсменов</p>
                                    </div>
                                    <div class="col-md-12 cols">
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/teams.png') }}" alt="">
                                            <h3>создавай и находи команды</h3>
                                            <p>Устраивай соревнования, ищи противников и приглашай болельщиков</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/child.png') }}" alt="">
                                            <h3>принимай участие</h3>
                                            <p>Следи за спортивными мероприятиями твоего города вместе с друзьями</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/master.png') }}" alt="">
                                            <h3>повышай мастерство</h3>
                                            <p>Находи наставников, получай советы и делись опытом</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/kurs.png') }}" alt="">
                                            <h3>будь в курсе</h3>
                                            <p>Общайся с единомышленниками, получай фото- и видеорепортажи</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 form-frame">
                        <div class="form-container">
                            <p class="sport-inside">Спорт внутри!</p>
                            <p>Не ограничивай себя. Зарегистрируйся и получи полный доступ ко всем возможностям сайта.</p>
                            <form autocomplete="off" name="enter-form" method="POST" id="entrance-form" action="{{ route('front.login') }}">
                                @csrf
                                <h3>Вход на сайт</h3>
                                @if ($errors->any())
                                    <div class="alert_msg"><p><strong>Ошибка! </strong>{{ $errors->first() }}</p></div>
                                @endif
                                <input type="email" name="username" value="{{ old('username', $email ?? '') }}" placeholder="email" id="input-login" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                                <input type="password" name="password" placeholder="Пароль" id="input-password" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                                <input type="checkbox" value="1" name="remember_me" checked id="input-checkbox" hidden>
                                <label for="input-checkbox">Запомнить меня</label>
                                <a href="#" class="form-enter-link_pass">Напомнить пароль</a>
                                <input type="submit" name="login" value="Войти" id="input-submit">
                                <span>или</span>
                                <a href="#" class="form-enter-link_reg">Зарегистрироваться</a>
                            </form>
                            <div class="social">
                                <h4>Войти через</h4>
                                <a href="{{ route('front.social.redirect', ['provider' => 'google']) }}" class="social-auth-link" aria-label="Войти через Google" title="Google">
                                    <img src="{{ asset('frontend/images/social-google.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'x']) }}" class="social-auth-link" aria-label="Войти через X" title="X">
                                    <img src="{{ asset('frontend/images/social-x.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'facebook']) }}" class="social-auth-link" aria-label="Войти через Facebook" title="Facebook">
                                    <img src="{{ asset('frontend/images/social-facebook.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'linkedin']) }}" class="social-auth-link" aria-label="Войти через LinkedIn" title="LinkedIn">
                                    <img src="{{ asset('frontend/images/social-linkedin.svg') }}" alt="">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<div class="footer">
    @include('front.partials.footer')
</div>
</body>
</html>
