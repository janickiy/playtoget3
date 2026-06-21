<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="We are the first sports online resource that brings together healthy lifestyle followers, sports fans and professional athletes.">
    <title>{{ $title ?? 'PlayToGet' }}</title>
    <link href="{{ asset('favicon.ico') }}" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}?v=2026062101">
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
                                        <h1>Sports social network</h1>
                                        <p class="desc">We are the first sports online resource that brings together:<br>
                                            healthy lifestyle followers, sports fans and professional athletes</p>
                                    </div>
                                    <div class="col-md-12 cols">
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/teams.png') }}" alt="">
                                            <h3>create and find teams</h3>
                                            <p>Organize competitions, find opponents and invite fans</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/child.png') }}" alt="">
                                            <h3>take part</h3>
                                            <p>Follow sports events in your city with friends</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/master.png') }}" alt="">
                                            <h3>improve your skills</h3>
                                            <p>Find mentors, get advice and share experience</p>
                                        </div>
                                        <div class="col-md-3">
                                            <img src="{{ asset('frontend/images/kurs.png') }}" alt="">
                                            <h3>stay up to date</h3>
                                            <p>Chat with like-minded people and get photo and video reports</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 form-frame">
                        <div class="form-container">
                            <p class="sport-inside">Sport inside!</p>
                            <p>Do not limit yourself. Sign up and get full access to all site features.</p>
                            @php($showResetForm = (bool) session('password_reset_mode'))
                            <form autocomplete="off" name="enter-form" method="POST" id="entrance-form" action="{{ route('front.login') }}" @style(['display: none' => $showResetForm])>
                                @csrf
                                <h3>Sign in to the site</h3>
                                @if (session('auth_status'))
                                    <div class="entrance-status-message">{{ session('auth_status') }}</div>
                                @endif
                                @if ($errors->any())
                                    <div class="alert_msg"><p><strong>Error! </strong>{{ $errors->first() }}</p></div>
                                @endif
                                <input type="email" name="username" value="{{ old('username', $email ?? 'demo.user02@playtoget.local') }}" placeholder="email" id="input-login" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                                <input type="password" name="password" value="DemoUser02!2026" placeholder="Password" id="input-password" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                                <input type="checkbox" value="1" name="remember_me" checked id="input-checkbox" hidden>
                                <label for="input-checkbox">Remember me</label>
                                <a href="#" class="form-enter-link_pass">Remind password</a>
                                <input type="submit" name="login" value="Sign in" id="input-submit">
                                <span>or</span>
                                <a href="{{ route('front.registration.form') }}" class="form-enter-link_reg">Sign up</a>
                            </form>
                            <form autocomplete="off" name="password-reset-form" method="POST" id="password-reset-request-form" action="{{ route('front.password.email') }}" @style(['display: none' => ! $showResetForm])>
                                @csrf
                                <h3>Password reset</h3>
                                @if (session('password_reset_status'))
                                    <div class="entrance-status-message">{{ session('password_reset_status') }}</div>
                                @endif
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="email" class="entrance-text-field" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
                                <div class="entrance-actions">
                                    <input type="submit" value="Send link" class="entrance-submit">
                                    <span>or</span>
                                    <a href="#" class="form-enter-link_login">Sign in</a>
                                </div>
                            </form>
                            <div class="social">
                                <h4>Sign in with</h4>
                                <a href="{{ route('front.social.redirect', ['provider' => 'google']) }}" class="social-auth-link" aria-label="Sign in with Google" title="Google">
                                    <img src="{{ asset('frontend/images/social-google.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'x']) }}" class="social-auth-link" aria-label="Sign in with X" title="X">
                                    <img src="{{ asset('frontend/images/social-x.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'facebook']) }}" class="social-auth-link" aria-label="Sign in with Facebook" title="Facebook">
                                    <img src="{{ asset('frontend/images/social-facebook.svg') }}" alt="">
                                </a>
                                <a href="{{ route('front.social.redirect', ['provider' => 'linkedin']) }}" class="social-auth-link" aria-label="Sign in with LinkedIn" title="LinkedIn">
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
<script>
    $(function () {
        var $loginForm = $('#entrance-form');
        var $resetForm = $('#password-reset-request-form');

        $('.form-enter-link_pass').on('click', function (event) {
            event.preventDefault();
            $loginForm.hide();
            $resetForm.show();
        });

        $('.form-enter-link_login').on('click', function (event) {
            event.preventDefault();
            $resetForm.hide();
            $loginForm.show();
        });
    });
</script>
</body>
</html>
