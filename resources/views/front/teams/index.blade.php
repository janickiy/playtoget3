@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @empty($viewedUserId)
            <form autocomplete="off" action="{{ route('front.teams.index') }}" method="GET" role="search">
                <div class="add-photos-album selects-field-events teams-search-form">
                    <div class="select-container-text two_block">
                        <input type="hidden" name="id_place" class="id_place" value="{{ request('id_place') }}" data-type="search_city">
                        <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('place') }}" name="place" data-type="search_city" placeholder="Ищу команду в городе">
                        <div class="select-place" data-type="search_city"></div>
                    </div>
                    <div class="select-container-text two_block borderLeft">
                        <input type="hidden" name="id_sport" class="id_place" value="{{ request('id_sport') }}" data-type="search_sport">
                        <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('sport') }}" name="sport" data-type="search_sport" placeholder="Ищу свой спорт">
                        <div class="select-place" data-type="search_sport"></div>
                    </div>
                    <p class="select-container-text lupa">
                        <input type="text" name="search" value="{{ request('search') }}" class="search_word" placeholder="Ключевое слово">
                        <span></span>
                    </p>
                    <input type="submit" class="displayNone">
                    <button type="button" onclick="location.href='{{ route('front.teams.create') }}'" class="btn btn-white">Создать команду</button>
                </div>
            </form>

            <div id="tabs">
                <ul id="main-menu" class="marginBottom-40">
                    <li data-type="popular" class="active"><a href="#popular">Популярные команды</a></li>
                    <li data-type="mygroups">
                        <a href="#mygroups">Мои команды
                            @if ($myTeamsTotal > 0)
                                <sup>{{ $myTeamsTotal }}</sup>
                            @endif
                        </a>
                    </li>
                    <li data-type="invited">
                        <a href="#invited">Меня пригласили
                            @if ($invitedTeamsTotal > 0)
                                <sup class="active">{{ $invitedTeamsTotal }}</sup>
                            @endif
                        </a>
                    </li>
                </ul>

                <div id="popular" class="paddingTop20">
                    @if ($popularTeams->isNotEmpty())
                        <div class="event-container">
                            <div id="pop_team_list"
                                 data-next-offset="{{ $popularTeams->count() }}"
                                 data-page-size="{{ $teamsPageSize }}"
                                 data-has-more="{{ $popularTeamsTotal > $popularTeams->count() ? 1 : 0 }}">
                                @foreach ($popularTeams as $team)
                                    @include('front.teams._team-card', ['team' => $team])
                                @endforeach
                            </div>
                            <a href="#" class="show-more js-teams-load-more" data-feed="popular" @style(['display: none' => $popularTeamsTotal <= $popularTeams->count()])>
                                <i></i><span>Показать еще</span>
                            </a>
                        </div>
                    @else
                        <div class="text-center"><h5>Популярных команд пока нет.</h5></div>
                    @endif
                </div>

                <div id="mygroups" class="paddingTop20" style="display:none">
                    @if ($myTeams->isNotEmpty())
                        <div class="event-container">
                            <div id="my_team_list"
                                 data-next-offset="{{ $myTeams->count() }}"
                                 data-page-size="{{ $teamsPageSize }}"
                                 data-has-more="{{ $myTeamsTotal > $myTeams->count() ? 1 : 0 }}"
                                 data-user-id="{{ $viewer->id }}">
                                @foreach ($myTeams as $team)
                                    @include('front.teams._team-card', ['team' => $team])
                                @endforeach
                            </div>
                            <a href="#" class="show-more js-teams-load-more" data-feed="mygroups" @style(['display: none' => $myTeamsTotal <= $myTeams->count()])>
                                <i></i><span>Показать еще</span>
                            </a>
                        </div>
                    @else
                        <div class="text-center"><h5>Вы еще не вступили ни в одну команду.</h5></div>
                    @endif
                </div>

                <div id="invited" class="paddingTop20" style="display:none">
                    @if ($invitedTeams->isNotEmpty())
                        <div class="event-container">
                            <div id="invited_team_list"
                                 data-next-offset="{{ $invitedTeams->count() }}"
                                 data-page-size="{{ $teamsPageSize }}"
                                 data-has-more="{{ $invitedTeamsTotal > $invitedTeams->count() ? 1 : 0 }}"
                                 data-user-id="{{ $viewer->id }}">
                                @foreach ($invitedTeams as $team)
                                    @include('front.teams._team-card', ['team' => $team, 'inviteActions' => true])
                                @endforeach
                            </div>
                            <a href="#" class="show-more js-teams-load-more" data-feed="invited" @style(['display: none' => $invitedTeamsTotal <= $invitedTeams->count()])>
                                <i></i><span>Показать еще</span>
                            </a>
                        </div>
                    @else
                        <div class="text-center"><h5>У вас нет приглашений.</h5></div>
                    @endif
                </div>
            </div>
        @else
            <div class="photo-caption">
                <h3>Команды<sup>{{ $myTeams->count() }}</sup></h3>
            </div>

            @if ($myTeams->isNotEmpty())
                <div class="event-container">
                    @foreach ($myTeams as $team)
                        @include('front.teams._team-card', ['team' => $team])
                    @endforeach
                </div>
            @else
                <p class="no_message">Команд пока нет.</p>
            @endif
        @endempty
    </div>
@endsection

@include('front.communities._invite-list-actions-assets')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
    <style>
        .content-groups .event-container .event-item p.team-members {
            color: #929292;
            font-size: 12px;
            font-weight: 100;
            margin-bottom: 4px;
        }

        .content-groups .event-container .event-item p.team-members i {
            background: url('{{ asset('frontend/images/icon-running.png') }}') no-repeat;
            display: inline-block;
            height: 14px;
            margin-right: 5px;
            vertical-align: -2px;
            width: 18px;
        }

        .content-groups .teams-search-form:after {
            clear: both;
            content: "";
            display: block;
        }

        .content-groups #tabs {
            clear: both;
            margin-top: 10px;
        }

        .content-groups #tabs #main-menu.marginBottom-40 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 0 0 20px !important;
            padding: 0;
        }

        .content-groups #tabs #main-menu li {
            box-sizing: border-box;
            float: none;
            font-size: 13px;
            font-weight: 400;
            line-height: 40px;
            list-style: none;
            margin: 0;
            min-width: 0;
            padding: 0 !important;
            position: relative;
            text-align: center !important;
            width: 100% !important;
        }

        .content-groups #tabs #main-menu li a {
            box-sizing: border-box;
            display: block;
            height: 40px;
            line-height: 40px;
            padding: 0;
            text-align: center;
            white-space: nowrap;
            width: 100%;
        }

        .content-groups #tabs #main-menu li sup {
            border-radius: 50px;
            display: block;
            height: 15px;
            line-height: 15px;
            position: absolute;
            right: 5px;
            text-align: center;
            top: 5px;
            width: 15px;
        }

        .content-groups #tabs > .paddingTop20 {
            clear: both;
            padding-top: 0 !important;
        }

        .content-groups .show-more.teams-loading {
            opacity: .6;
            pointer-events: none;
        }

        .content-groups .teams-search-form .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            max-height: 260px;
            top: 100%;
            z-index: 100;
        }

        .content-groups .teams-search-form .select-place .place-item {
            font-size: 18px;
            line-height: 30px;
            padding: 6px 10px;
            text-align: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/search.js') }}"></script>
    <script>
        (function () {
            const $tabs = $('#tabs');

            if (!$tabs.length) {
                return;
            }

            function activateTab(selector) {
                $tabs.children('div').hide();
                $(selector).show();
                $tabs.find('#main-menu li').removeClass('active ui-state-active');
                $tabs.find('#main-menu a[href="' + selector + '"]').closest('li').addClass('active ui-state-active');
                requestNextPageIfNeeded();
            }

            const feeds = {
                popular: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_pop_communities_list']) }}',
                    $container: $('#pop_team_list'),
                },
                mygroups: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_communities_list']) }}',
                    $container: $('#my_team_list'),
                },
                invited: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_communities_list']) }}',
                    $container: $('#invited_team_list'),
                },
            };

            let loading = false;
            let scrollTimer = null;

            function searchPayload() {
                const payload = {};
                const params = new URLSearchParams(window.location.search);

                ['id_place', 'place', 'id_sport', 'sport', 'search'].forEach(function (name) {
                    payload[name] = params.get(name) || '';
                });

                return payload;
            }

            function activeFeedName() {
                return $tabs.find('#main-menu li.active').data('type');
            }

            function feedConfig(feedName) {
                const config = feeds[feedName];

                if (!config || !config.$container.length) {
                    return null;
                }

                return config;
            }

            function loadTeams(feedName) {
                const config = feedConfig(feedName);

                if (!config || loading || Number(config.$container.data('has-more')) !== 1) {
                    return;
                }

                const $button = $('.js-teams-load-more[data-feed="' + feedName + '"]');
                const pageSize = Number(config.$container.data('page-size')) || 5;
                const offset = Number(config.$container.data('next-offset')) || 0;

                loading = true;
                $button.addClass('teams-loading');

                $.ajax({
                    type: 'POST',
                    url: config.url,
                    data: Object.assign({
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        number: pageSize,
                        offset: offset,
                        type: 'team',
                        feed: feedName,
                        user_id: config.$container.data('user-id') || '{{ $viewer?->id ?? 0 }}',
                    }, searchPayload()),
                    success: function (data) {
                        if (data.status === 1 && data.html) {
                            config.$container.append(data.html);
                            config.$container.data('next-offset', offset + (Number(data.count) || pageSize));
                            config.$container.data('has-more', data.has_more ? 1 : 0);
                        } else {
                            config.$container.data('has-more', 0);
                        }

                        if (Number(config.$container.data('has-more')) !== 1) {
                            $button.hide();
                        }
                    },
                    complete: function () {
                        loading = false;
                        $button.removeClass('teams-loading');
                        requestNextPageIfNeeded();
                    },
                });
            }

            function requestNextPageIfNeeded() {
                window.clearTimeout(scrollTimer);
                scrollTimer = window.setTimeout(function () {
                    if ($(window).scrollTop() + $(window).height() + 300 >= $(document).height()) {
                        loadTeams(activeFeedName());
                    }
                }, 80);
            }

            $tabs.find('#main-menu a').on('click', function (event) {
                event.preventDefault();
                activateTab($(this).attr('href'));
            });

            $('.js-teams-load-more').on('click', function (event) {
                event.preventDefault();
                loadTeams($(this).data('feed'));
            });

            $(window).on('scroll.teams', requestNextPageIfNeeded);

            $('.teams-search-form .lupa span').on('click', function () {
                $(this).closest('form').trigger('submit');
            });

            activateTab('#popular');
        })();
    </script>
@endpush
