@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @empty($viewedUserId)
        <form autocomplete="off" action="{{ route('front.groups.index') }}" method="GET" role="search">
            <div class="add-photos-album selects-field-events groups-search-form">
                <div class="select-container-text two_block">
                    <input type="hidden" name="id_place" class="id_place" value="{{ request('id_place') }}" data-type="search_city">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('place') }}" name="place" data-type="search_city" placeholder="Ищу группу в городе">
                    <div class="select-place" data-type="search_city"></div>
                </div>
                <div class="select-container-text two_block borderLeft">
                    <input type="hidden" name="id_sport" class="id_place" value="{{ request('id_sport') }}" data-type="search_sport">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('sport') }}" name="sport" data-type="search_sport" placeholder="Ищу вид спорта">
                    <div class="select-place" data-type="search_sport"></div>
                </div>
                <p class="select-container-text lupa">
                    <input type="text" name="search" value="{{ request('search') }}" class="search_word" placeholder="Ключевое слово">
                    <span></span>
                </p>
                <input type="submit" class="displayNone">
                <button type="button" onclick="location.href='{{ route('front.groups.create') }}'" class="btn btn-white">Создать группу</button>
            </div>
        </form>

        <div class="photo-caption front-section-title">
            <h3>Группы</h3>
        </div>

        <div id="tabs">
            <ul id="main-menu" class="marginBottom-40">
                <li data-type="popular" class="active"><a href="#popular">Популярные группы</a></li>
                <li data-type="mygroups">
                    <a href="#mygroups">Мои группы
                        @if ($myGroupsTotal > 0)
                            <sup>{{ $myGroupsTotal }}</sup>
                        @endif
                    </a>
                </li>
                <li data-type="invited">
                    <a href="#invited">Меня пригласили
                        @if ($invitedGroupsTotal > 0)
                            <sup class="active">{{ $invitedGroupsTotal }}</sup>
                        @endif
                    </a>
                </li>
            </ul>

            <div id="popular" class="paddingTop20">
                @if ($popularGroups->isNotEmpty())
                    <div class="event-container">
                        <div id="pop_group_list"
                             data-next-offset="{{ $popularGroups->count() }}"
                             data-page-size="{{ $groupsPageSize }}"
                             data-has-more="{{ $popularGroupsTotal > $popularGroups->count() ? 1 : 0 }}">
                            @foreach ($popularGroups as $group)
                                @include('front.groups._group-card', ['group' => $group])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-groups-load-more" data-feed="popular" @style(['display: none' => $popularGroupsTotal <= $popularGroups->count()])>
                            <i></i><span>Показать еще</span>
                        </a>
                    </div>
                @else
                    <div class="text-center"><h5>Популярные группы отсутствуют</h5></div>
                @endif
            </div>

            <div id="mygroups" class="paddingTop20" style="display:none">
                @if ($myGroups->isNotEmpty())
                    <div class="event-container">
                        <div id="my_group_list"
                             data-next-offset="{{ $myGroups->count() }}"
                             data-page-size="{{ $groupsPageSize }}"
                             data-has-more="{{ $myGroupsTotal > $myGroups->count() ? 1 : 0 }}"
                             data-user-id="{{ $viewer->id }}">
                            @foreach ($myGroups as $group)
                                @include('front.groups._group-card', ['group' => $group])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-groups-load-more" data-feed="mygroups" @style(['display: none' => $myGroupsTotal <= $myGroups->count()])>
                            <i></i><span>Показать еще</span>
                        </a>
                    </div>
                @else
                    <div class="text-center"><h5>Вы пока не вступали в группы</h5></div>
                @endif
            </div>

            <div id="invited" class="paddingTop20" style="display:none">
                @if ($invitedGroups->isNotEmpty())
                    <div class="event-container">
                        <div id="invited_group_list"
                             data-next-offset="{{ $invitedGroups->count() }}"
                             data-page-size="{{ $groupsPageSize }}"
                             data-has-more="{{ $invitedGroupsTotal > $invitedGroups->count() ? 1 : 0 }}"
                             data-user-id="{{ $viewer->id }}">
                            @foreach ($invitedGroups as $group)
                                @include('front.groups._group-card', ['group' => $group, 'inviteActions' => true])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-groups-load-more" data-feed="invited" @style(['display: none' => $invitedGroupsTotal <= $invitedGroups->count()])>
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
                <h3>Группы<sup>{{ $myGroups->count() }}</sup></h3>
            </div>

            @if ($myGroups->isNotEmpty())
                <div class="event-container">
                    @foreach ($myGroups as $group)
                        @include('front.groups._group-card', ['group' => $group])
                    @endforeach
                </div>
            @else
                <p class="no_message">Групп пока нет.</p>
            @endif
        @endempty
    </div>
@endsection

@include('front.communities._invite-list-actions-assets')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
    <style>
        .content-groups .event-container .event-item p.group-members {
            color: #929292;
            font-size: 12px;
            font-weight: 100;
            margin-bottom: 4px;
        }

        .content-groups .event-container .event-item p.group-members i {
            background: url('{{ asset('frontend/images/icon-running.png') }}') no-repeat;
            display: inline-block;
            height: 14px;
            margin-right: 5px;
            vertical-align: -2px;
            width: 18px;
        }

        .content-groups .groups-search-form:after {
            clear: both;
            content: "";
            display: block;
        }

        .content-groups #tabs {
            clear: both;
            margin-top: 10px;
        }

        .content-groups #tabs #main-menu.marginBottom-40 {
            column-gap: 3px;
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

        .content-groups .show-more.groups-loading {
            opacity: .6;
            pointer-events: none;
        }

        .content-groups .groups-search-form .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            max-height: 260px;
            top: 100%;
            z-index: 100;
        }

        .content-groups .groups-search-form .select-place .place-item {
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
                        $container: $('#pop_group_list'),
                    },
                    mygroups: {
                        url: '{{ route('front.ajax.handle', ['action' => 'get_communities_list']) }}',
                        $container: $('#my_group_list'),
                    },
                    invited: {
                        url: '{{ route('front.ajax.handle', ['action' => 'get_communities_list']) }}',
                        $container: $('#invited_group_list'),
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

                function loadGroups(feedName) {
                    const config = feedConfig(feedName);

                    if (!config || loading || Number(config.$container.data('has-more')) !== 1) {
                        return;
                    }

                    const $button = $('.js-groups-load-more[data-feed="' + feedName + '"]');
                    const pageSize = Number(config.$container.data('page-size')) || 5;
                    const offset = Number(config.$container.data('next-offset')) || 0;

                    loading = true;
                    $button.addClass('groups-loading');

                    $.ajax({
                        type: 'POST',
                        url: config.url,
                        data: Object.assign({
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            number: pageSize,
                            offset: offset,
                            type: 'group',
                            feed: feedName,
                            user_id: config.$container.data('user-id') || '{{ $viewer->id }}',
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
                            $button.removeClass('groups-loading');
                            requestNextPageIfNeeded();
                        },
                    });
                }

                function requestNextPageIfNeeded() {
                    window.clearTimeout(scrollTimer);
                    scrollTimer = window.setTimeout(function () {
                        if ($(window).scrollTop() + $(window).height() + 300 >= $(document).height()) {
                            loadGroups(activeFeedName());
                        }
                    }, 80);
                }

	            $tabs.find('#main-menu a').on('click', function (event) {
	                event.preventDefault();
	                activateTab($(this).attr('href'));
	            });

                $('.js-groups-load-more').on('click', function (event) {
                    event.preventDefault();
                    loadGroups($(this).data('feed'));
                });

                $(window).on('scroll.groups', requestNextPageIfNeeded);

	            $('.groups-search-form .lupa span').on('click', function () {
	                $(this).closest('form').trigger('submit');
	            });

            activateTab('#popular');
        })();
    </script>
@endpush
