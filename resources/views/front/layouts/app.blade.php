<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="no-cache">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:image" content="{{ asset('frontend/images/left-sitebar-img-2.png') }}">
    <meta name="description" content="We are the first sports online resource that brings together: healthy lifestyle followers, sports fans and professional athletes.">
    <title>{{ $title ?? 'PlayToGet' }}</title>
    <link href="{{ asset('favicon.ico') }}" rel="shortcut icon" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap-theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}?v=2026062208">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.theme.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/owl.transitions.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/jquery.confirm.css') }}?v=2026061504">
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
    <script src="{{ asset('frontend/js/header.js') }}?v=2026061601"></script>
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
                                <a href="{{ route('front.home') }}"><img src="{{ asset('frontend/images/playtoget-logo-clean.svg') }}?v=2026061404" width="145" height="43" alt=""></a>
                            </div>
                            <div class="search">
                                <form autocomplete="off" action="{{ route('front.home') }}" method="GET">
                                    <input type="text" name="search" id="main_search" value="{{ request('search') }}" placeholder="Find">
                                </form>
                            </div>
                        </div>
                        <div class="profile-user">
                            <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                <div class="mini_thumb_avatar avatar-status-holder">
                                    <img width="50" height="50" src="{{ $frontLayout['avatar'] }}" alt="">
                                </div>
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
                                        <div class="mini_thumb_avatar avatar-status-holder">
                                            <img width="50" height="50" src="{{ $frontLayout['avatar'] }}" alt="">
                                        </div>
                                    </a>
                                    <a href="{{ $frontLayout['user'] ? route('front.profile.show', ['user' => $frontLayout['user']->id]) : route('front.home') }}">{{ $frontLayout['firstname'] }}<span></span>{{ $frontLayout['lastname'] }}<span></span></a>
                                </li>
                                <li><a href="{{ route('front.home') }}"><img src="{{ asset('frontend/images/menu-home.svg') }}" width="25" height="28" alt=""></a></li>
                                <li class="header-counter-item">
                                    <a href="{{ $frontLayout['user'] ? route('front.profile.messages.index', ['user' => $frontLayout['user']->id]) : route('front.home') }}">
                                        <img src="{{ asset('frontend/images/message.svg') }}" width="29" height="24" alt="">
                                    </a>
                                    <span id="message_count" class="header-counter {{ ($frontLayout['unreadDialoguesCount'] ?? 0) > 0 ? '' : 'displayNone' }}">{{ $frontLayout['unreadDialoguesCount'] ?? 0 }}</span>
                                </li>
                                <li class="header-counter-item">
                                    <a href="{{ route('front.friends.index') }}"><img src="{{ asset('frontend/images/man.svg') }}?v=2026061601" width="34" height="30" alt=""></a>
                                    <span id="friend_request_count" class="header-counter {{ ($frontLayout['incomingFriendRequestsCount'] ?? 0) > 0 ? '' : 'displayNone' }}">{{ $frontLayout['incomingFriendRequestsCount'] ?? 0 }}</span>
                                </li>
                                <li><a href="{{ route('front.profile.edit') }}"><img src="{{ asset('frontend/images/settings.svg') }}?v=2026061601" width="28" height="28" alt=""></a></li>
                                <li>
                                    <form method="POST" action="{{ route('front.logout') }}">
                                        @csrf
                                        <button type="submit"><b>X</b> Log out</button>
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
                    @php
                        $profileUrl = route('front.profile.show', ['user' => $frontLayout['user']?->id ?? 1]);
                        $isHomeActive = request()->routeIs('front.home') || request()->routeIs('front.news.*');
                        $isProfileActive = request()->routeIs('front.profile.*');
                        $isFriendsActive = request()->routeIs('front.friends.*');
                        $isShareActive = request()->routeIs('front.photoalbums.*') || request()->routeIs('front.videoalbums.*');
                        $isTeamsActive = request()->routeIs('front.teams.*');
                        $isGroupsActive = request()->routeIs('front.groups.*') || request()->routeIs('front.playgrounds.*') || request()->routeIs('front.shops.*') || request()->routeIs('front.fitness.*');
                        $isEventsActive = request()->routeIs('front.events.*');
                        $isCalendarActive = request()->routeIs('front.calendar.*');
                    @endphp
                    <ul class="menu">
                        <li class="{{ $isHomeActive ? 'active' : '' }}">
                            <a href="{{ route('front.home') }}"><img src="{{ asset('frontend/images/main-menu-home.svg') }}?v=2026061603" width="34" height="36" alt=""></a>
                            <span class="for-submenu">
                                <a title="Home" href="{{ route('front.home') }}">Home</a>
                            </span>
                        </li>
                        <li class="{{ $isProfileActive ? 'active' : '' }}">
                            <a href="{{ $profileUrl }}"><img src="{{ asset('frontend/images/main-menu-profile.svg') }}?v=2026061603" width="32" height="38" alt=""></a>
                            <span class="for-submenu">
                                <a title="Profile" href="{{ $profileUrl }}">Profile</a>
                            </span>
                        </li>
                        <li class="{{ $isFriendsActive ? 'active' : '' }}"><a href="{{ route('front.friends.index') }}"><img src="{{ asset('frontend/images/main-menu-friends.svg') }}?v=2026061603" width="52" height="38" alt=""></a><span class="for-submenu"><a title="Friends" href="{{ route('front.friends.index') }}">Friends</a></span></li>
                        <li class="{{ $isShareActive ? 'active' : '' }}">
                            <a><img src="{{ asset('frontend/images/main-menu-share.svg') }}?v=2026061603" width="48" height="34" alt=""></a>
                            <span class="for-submenu">
                                <a>Share</a>
                                <ul class="top-mnu-submenu">
                                    <li><a href="{{ route('front.photoalbums.index') }}">Photos</a></li>
                                    <li><a href="{{ route('front.videoalbums.index') }}">Video</a></li>
                                </ul>
                            </span>
                        </li>
                        <li class="{{ $isTeamsActive ? 'active' : '' }}"><a href="{{ route('front.teams.index') }}"><img src="{{ asset('frontend/images/main-menu-teams.svg') }}?v=2026061603" width="40" height="34" alt=""></a><span class="for-submenu"><a title="Teams" href="{{ route('front.teams.index') }}">Teams</a></span></li>
                        <li class="menu_groups_hide {{ $isGroupsActive ? 'active' : '' }}"><a href="{{ route('front.groups.index') }}"><img src="{{ asset('frontend/images/main-menu-groups.svg') }}?v=2026061603" width="56" height="34" alt=""></a><span class="for-submenu"><a title="Groups" href="{{ route('front.groups.index') }}">Groups</a></span></li>
                        <li class="menu_groups {{ $isGroupsActive ? 'active' : '' }}">
                            <a><img src="{{ asset('frontend/images/main-menu-groups.svg') }}?v=2026061603" width="56" height="34" alt=""></a>
                            <span class="for-submenu">
                                <a title="Groups" href="{{ route('front.groups.index') }}">Groups</a>
                                <ul class="top-mnu-submenu">
                                    <li><a href="{{ route('front.groups.index') }}">Groups</a></li>
                                    <li><a href="{{ route('front.playgrounds.index') }}">Playgrounds</a></li>
                                    <li><a href="{{ route('front.shops.index') }}">Shops</a></li>
                                    <li><a href="{{ route('front.fitness.index') }}">Fitness</a></li>
                                </ul>
                            </span>
                        </li>
                        <li class="{{ $isEventsActive ? 'active' : '' }}"><a href="{{ route('front.events.index') }}"><img src="{{ asset('frontend/images/main-menu-events.svg') }}?v=2026061603" width="36" height="40" alt=""></a><span class="for-submenu"><a title="Events" href="{{ route('front.events.index') }}">Events</a></span></li>
                        <li class="{{ $isCalendarActive ? 'active' : '' }}"><a href="{{ route('front.calendar.index') }}"><img src="{{ asset('frontend/images/main-menu-calendar.svg') }}?v=2026061603" width="42" height="40" alt=""></a><span class="for-submenu"><a title="Calendar" href="{{ route('front.calendar.index') }}">Calendar</a></span></li>
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

    <script src="{{ asset('frontend/js/script_all.js') }}?v=2026061904"></script>
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
