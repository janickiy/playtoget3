<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:image" content="{{ asset('frontend/images/left-sitebar-img-2.png') }}">
    <meta name="description" content="Мы первый спортивный интернет-ресурс, объединивший: приверженцев здорового образа жизни, любителей спорта и профессиональных спортсменов.">
    <title>{{ $title ?? 'PlayToGet' }}</title>
    <link href="{{ asset('favicon.ico') }}" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap-theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}?v=2026061517">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.theme.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.transitions.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery.confirm.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/lightbox.css') }}?v=2.12.0">
    <link rel="stylesheet" href="{{ asset('frontend/css/responsive.css') }}?v=2026061504">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1440.css') }}" media="(max-width: 1440px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-1190.css') }}" media="(max-width: 1190px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-960.css') }}?v=2026061404" media="(max-width: 960px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-768.css') }}" media="(max-width: 768px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-640.css') }}" media="(max-width: 640px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-480.css') }}?v=2026061501" media="(max-width: 480px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/max-width-390.css') }}?v=2026061501" media="(max-width: 390px)">
    <link rel="stylesheet" href="{{ asset('frontend/css/emotions.css') }}?v=2026061502">
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery.emotions.fb.css') }}?v=2026061502">
    @stack('styles')
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800&subset=latin,cyrillic" rel="stylesheet" type="text/css">
    <script src="{{ asset('frontend/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('frontend/js/header.js') }}"></script>
    <script src="{{ asset('frontend/js/show-hidden.js') }}"></script>
    <script>
        window.user = '{{ $frontLayout['user']?->id ?? '' }}';
        window.avatar = '{{ $frontLayout['avatar'] }}';
        window.user_id = '{{ $frontLayout['user']?->id ?? '' }}';
    </script>
</head>
<body>
<div id="tooltip"></div>
<div class="window-message"></div>
<div class="main">
    <section class="wrapper header">
        <div class="container-fluid">
            <div class="row">
                <div class="top-header">
                    <div class="col-xs-12">
                        <div class="left-top-header">
                            <div class="logo">
                                <a href="{{ route('front.news.index') }}"><img src="{{ asset('frontend/images/playtoget-logo-clean.svg') }}?v=2026061404" width="145" height="43" alt=""></a>
                            </div>
                            <div class="search">
                                <form autocomplete="off" action="{{ route('front.news.index') }}" method="GET">
                                    <input type="text" name="search" id="main_search" value="{{ request('search') }}" placeholder="Найти">
                                </form>
                            </div>
                        </div>
                        <div class="profile-user">
                            <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                <div class="mini_thumb_avatar"><img width="50" height="50" src="{{ $frontLayout['avatar'] }}" alt=""></div>
                            </a>
                            <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                {{ $frontLayout['firstname'] }}<br>{{ $frontLayout['lastname'] }}
                            </a>
                        </div>
                        <a class="menu-icon" href="#go-nav"></a>
                        <div class="top-header-menu">
                            <ul>
                                <li>
                                    <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                        <div class="mini_thumb_avatar"><img width="50" height="50" src="{{ $frontLayout['avatar'] }}" alt=""></div>
                                    </a>
                                    <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">{{ $frontLayout['firstname'] }}<span></span>{{ $frontLayout['lastname'] }}<span></span></a>
                                </li>
                                <li><a href="{{ route('front.news.index') }}"><img src="{{ asset('frontend/images/menu-home.svg') }}" width="25" height="28" alt=""></a></li>
                                <li>
                                    <a href="{{ $frontLayout['user'] ? route('front.profile.messages.index', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                        <img src="{{ asset('frontend/images/message.svg') }}" width="29" height="24" alt="">
                                    </a>
                                    <span id="message_count" class="displayNone">0</span>
                                </li>
                                <li><a href="{{ route('front.friends.index') }}"><img src="{{ asset('frontend/images/man.svg') }}" width="24" height="30" alt=""></a></li>
                                <li><a href="{{ route('front.profile.edit') }}"><img src="{{ asset('frontend/images/settings.svg') }}" width="25" height="25" alt=""></a></li>
                                <li>
                                    <form method="POST" action="{{ route('front.logout') }}">
                                        @csrf
                                        <button type="submit"><b>X</b> Выйти</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="wrapper mnu">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <ul class="menu">
                        <li>
                            <a><img src="{{ asset('frontend/images/Profile.png') }}" alt=""></a>
                            <span class="for-submenu">
                                <a>Профиль</a>
                                <ul class="top-mnu-submenu">
                                    <li><a href="{{ route('front.profile.show', ['user' => $frontLayout['user']?->id ?? 1]) }}">Профиль</a></li>
                                    <li><a href="{{ route('front.profile.edit') }}">Редактировать</a></li>
                                </ul>
                            </span>
                        </li>
                        <li><a href="{{ route('front.news.index') }}"><img src="{{ asset('frontend/images/news.png') }}" alt=""></a><span class="for-submenu"><a title="Новости" href="{{ route('front.news.index') }}">Новости</a></span></li>
                        <li><a href="{{ route('front.friends.index') }}"><img src="{{ asset('frontend/images/friends.png') }}" alt=""></a><span class="for-submenu"><a title="Друзья" href="{{ route('front.friends.index') }}">Друзья</a></span></li>
                        <li>
                            <a><img src="{{ asset('frontend/images/Share.png') }}" alt=""></a>
                            <span class="for-submenu">
                                <a>Поделиться</a>
                                <ul class="top-mnu-submenu">
                                    <li><a href="{{ route('front.photoalbums.index') }}">Фотографии</a></li>
                                    <li><a href="{{ route('front.videoalbums.index') }}">Видео</a></li>
                                </ul>
                            </span>
                        </li>
                        <li><a href="{{ route('front.teams.index') }}"><img src="{{ asset('frontend/images/command.png') }}" alt=""></a><span class="for-submenu"><a title="Команды" href="{{ route('front.teams.index') }}">Команды</a></span></li>
                        <li class="menu_groups_hide"><a href="{{ route('front.groups.index') }}"><img src="{{ asset('frontend/images/Group.png') }}" alt=""></a><span class="for-submenu"><a title="Группы" href="{{ route('front.groups.index') }}">Группы</a></span></li>
                        <li class="menu_groups">
                            <a><img src="{{ asset('frontend/images/Group.png') }}" alt=""></a>
                            <span class="for-submenu">
                                <a title="Группы" href="{{ route('front.groups.index') }}">Группы</a>
                                <ul class="top-mnu-submenu">
                                    <li><a href="{{ route('front.groups.index') }}">Группы</a></li>
                                    <li><a href="{{ route('front.playgrounds.index') }}">Площадки</a></li>
                                    <li><a href="{{ route('front.shops.index') }}">Магазины</a></li>
                                    <li><a href="{{ route('front.fitness.index') }}">Фитнес</a></li>
                                </ul>
                            </span>
                        </li>
                        <li><a href="{{ route('front.events.index') }}"><img src="{{ asset('frontend/images/Events.png') }}" alt=""></a><span class="for-submenu"><a title="Мероприятия" href="{{ route('front.events.index') }}">Мероприятия</a></span></li>
                        <li><a href="{{ route('front.calendar.index') }}"><img src="{{ asset('frontend/images/Calendar.png') }}" alt=""></a><span class="for-submenu"><a title="Календарь" href="{{ route('front.calendar.index') }}">Календарь</a></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="wrapper">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div class="baner"></div>
                </div>
            </div>
        </div>
    </section>

    <script src="{{ asset('frontend/js/script_all.js') }}?v=2026061501"></script>
    <script src="{{ asset('frontend/js/lightbox.min.js') }}?v=2.12.0"></script>

    @include('front.partials.video-window')
    @include('front.partials.photo-window')

    <section class="wrapper">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 bg">
                    @include('front.partials.left-sidebar')
                    <div class="content">
                        @unless($hideTopProfile ?? false)
                            @include('front.partials.top-profile')
                        @endunless
                        @yield('content')
                    </div>
                    @include('front.partials.right-sidebar')
                </div>
            </div>
        </div>
    </section>

    @include('front.partials.footer')

    @stack('scripts')
</div>
</body>
</html>
