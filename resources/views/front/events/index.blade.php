@extends('front.layouts.app')

@php
    $dateFilter = (string) request('date');
    $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter) === 1
        && checkdate((int) substr($dateFilter, 5, 2), (int) substr($dateFilter, 8, 2), (int) substr($dateFilter, 0, 4))
        ? \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $dateFilter)
        : null;
@endphp

@section('content')
    <div class="content-groups friends">
        <form autocomplete="off" action="{{ route('front.events.index') }}" method="GET" role="search">
            <div class="add-photos-album selects-field-events events-search-form">
                <input type="hidden" name="date" value="{{ request('date') }}">
                <div class="select-container-text two_block">
                    <input type="hidden" name="id_place" class="id_place" value="{{ request('id_place') }}" data-type="search_city">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('place') }}" name="place" data-type="search_city" placeholder="Search events by city">
                    <div class="select-place" data-type="search_city"></div>
                </div>
                <div class="select-container-text two_block borderLeft">
                    <input type="hidden" name="id_sport" class="id_place" value="{{ request('id_sport') }}" data-type="search_sport">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('sport') }}" name="sport" data-type="search_sport" placeholder="Search sport type">
                    <div class="select-place" data-type="search_sport"></div>
                </div>
                <p class="select-container-text lupa">
                    <input type="text" name="search" value="{{ request('search') }}" class="search_word" placeholder="Keyword">
                    <span></span>
                </p>
                <input type="submit" class="displayNone">
                @if ($viewer)
                    <button type="button" onclick="location.href='{{ route('front.events.create') }}'" class="btn btn-white">Create event</button>
                @endif
            </div>
        </form>

        <div class="photo-caption front-section-title">
            <h3>Events</h3>
        </div>

        @if ($selectedDate)
            <div class="events-date-filter">
                Events on {{ $selectedDate->format('d.m.Y') }}
                <a href="{{ route('front.events.index') }}">reset</a>
            </div>
        @endif

        <div id="tabs">
            <ul id="main-menu" class="marginBottom-40">
                <li data-type="popular" class="active"><a href="#popular">Popular events</a></li>
                <li data-type="mygroups">
                    <a href="#mygroups">My events
                        @if ($myEventsTotal > 0)
                            <sup>{{ $myEventsTotal }}</sup>
                        @endif
                    </a>
                </li>
                <li data-type="invited">
                    <a href="#invited">Invited
                        @if ($invitedEventsTotal > 0)
                            <sup class="active">{{ $invitedEventsTotal }}</sup>
                        @endif
                    </a>
                </li>
            </ul>

            <div id="popular" class="paddingTop20">
                @if ($popularEvents->isNotEmpty())
                    <div class="event-container">
                        <div id="pop_event_list"
                             data-next-offset="{{ $popularEvents->count() }}"
                             data-page-size="{{ $eventsPageSize }}"
                             data-has-more="{{ $popularEventsTotal > $popularEvents->count() ? 1 : 0 }}">
                            @foreach ($popularEvents as $event)
                                @include('front.events._event-card', ['event' => $event])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-events-load-more" data-feed="popular" @style(['display: none' => $popularEventsTotal <= $popularEvents->count()])>
                            <i></i><span>Show more</span>
                        </a>
                    </div>
                @else
                    <div class="text-center"><h5>There are no popular events yet.</h5></div>
                @endif
            </div>

            <div id="mygroups" class="paddingTop20" style="display:none">
                @if ($myEvents->isNotEmpty())
                    <div class="event-container">
                        <div id="my_event_list"
                             data-next-offset="{{ $myEvents->count() }}"
                             data-page-size="{{ $eventsPageSize }}"
                             data-has-more="{{ $myEventsTotal > $myEvents->count() ? 1 : 0 }}"
                             data-user-id="{{ $viewer?->id ?? 0 }}">
                            @foreach ($myEvents as $event)
                                @include('front.events._event-card', ['event' => $event])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-events-load-more" data-feed="mygroups" @style(['display: none' => $myEventsTotal <= $myEvents->count()])>
                            <i></i><span>Show more</span>
                        </a>
                    </div>
                @else
                    <div class="text-center"><h5>You are not participating in any events yet.</h5></div>
                @endif
            </div>

            <div id="invited" class="paddingTop20" style="display:none">
                @if ($invitedEvents->isNotEmpty())
                    <div class="event-container">
                        <div id="invited_event_list"
                             data-next-offset="{{ $invitedEvents->count() }}"
                             data-page-size="{{ $eventsPageSize }}"
                             data-has-more="{{ $invitedEventsTotal > $invitedEvents->count() ? 1 : 0 }}"
                             data-user-id="{{ $viewer?->id ?? 0 }}">
                            @foreach ($invitedEvents as $event)
                                @include('front.events._event-card', ['event' => $event])
                            @endforeach
                        </div>
                        <a href="#" class="show-more js-events-load-more" data-feed="invited" @style(['display: none' => $invitedEventsTotal <= $invitedEvents->count()])>
                            <i></i><span>Show more</span>
                        </a>
                    </div>
                @else
                    <div class="text-center"><h5>You have no invitations.</h5></div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
    <style>
        .content-groups .events-search-form:after {
            clear: both;
            content: "";
            display: block;
        }

        .content-groups #tabs {
            clear: both;
            margin-top: 10px;
        }

        .content-groups .events-date-filter {
            background: #eef7ff;
            border: 1px solid #c8dff3;
            border-radius: 4px;
            clear: both;
            color: #337ab7;
            font-size: 14px;
            margin: 14px 0;
            padding: 10px 14px;
        }

        .content-groups .events-date-filter a {
            float: right;
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

        .content-groups .show-more.events-loading {
            opacity: .6;
            pointer-events: none;
        }

        .content-groups .events-search-form .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            max-height: 260px;
            top: 100%;
            z-index: 100;
        }

        .content-groups .events-search-form .select-place .place-item {
            font-size: 18px;
            line-height: 30px;
            padding: 6px 10px;
            text-align: center;
        }

        .content-groups .event-container .event-item p.event-members {
            color: #929292;
            font-size: 12px;
            font-weight: 100;
            margin-bottom: 4px;
        }

        .content-groups .event-container .event-item p.event-members i {
            background: url('{{ asset('frontend/images/icon-running.png') }}') no-repeat;
            display: inline-block;
            height: 14px;
            margin-right: 5px;
            vertical-align: -2px;
            width: 18px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const tabs = document.getElementById('tabs');

            if (!tabs) {
                return;
            }

            function activateTab(selector) {
                tabs.querySelectorAll(':scope > div').forEach(function (panel) {
                    panel.style.display = panel.matches(selector) ? '' : 'none';
                });
                tabs.querySelectorAll('#main-menu li').forEach(function (item) {
                    item.classList.remove('active', 'ui-state-active');
                });
                const activeLink = tabs.querySelector('#main-menu a[href="' + selector + '"]');
                activeLink?.closest('li')?.classList.add('active', 'ui-state-active');
                requestNextPageIfNeeded();
            }

            const feeds = {
                popular: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_pop_events_list']) }}',
                    container: document.getElementById('pop_event_list'),
                },
                mygroups: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_events_list']) }}',
                    container: document.getElementById('my_event_list'),
                },
                invited: {
                    url: '{{ route('front.ajax.handle', ['action' => 'get_events_list']) }}',
                    container: document.getElementById('invited_event_list'),
                },
            };

            let loading = false;
            let scrollTimer = null;

            function searchPayload() {
                const payload = {};
                const params = new URLSearchParams(window.location.search);

                ['id_place', 'place', 'id_sport', 'sport', 'search', 'date'].forEach(function (name) {
                    payload[name] = params.get(name) || '';
                });

                return payload;
            }

            function feedConfig(feedName) {
                const config = feeds[feedName];

                if (!config || !config.container) {
                    return null;
                }

                return config;
            }

            function loadEvents(feedName) {
                const config = feedConfig(feedName);

                if (!config || loading || Number(config.container.dataset.hasMore) !== 1) {
                    return;
                }

                const button = document.querySelector('.js-events-load-more[data-feed="' + feedName + '"]');
                const pageSize = Number(config.container.dataset.pageSize) || 5;
                const offset = Number(config.container.dataset.nextOffset) || 0;
                const payload = new FormData();
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                Object.entries(Object.assign({
                    _token: token,
                    number: pageSize,
                    offset: offset,
                    feed: feedName,
                    user_id: config.container.dataset.userId || '{{ $viewer?->id ?? 0 }}',
                }, searchPayload())).forEach(function ([key, value]) {
                    payload.append(key, value);
                });

                loading = true;
                button?.classList.add('events-loading');

                fetch(config.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: payload,
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.ok ? response.json() : Promise.reject(response);
                    })
                    .then(function (data) {
                        if (data.status === 1 && data.html) {
                            config.container.insertAdjacentHTML('beforeend', data.html);
                            config.container.dataset.nextOffset = String(offset + (Number(data.count) || pageSize));
                            config.container.dataset.hasMore = data.has_more ? '1' : '0';
                        } else {
                            config.container.dataset.hasMore = '0';
                        }

                        if (Number(config.container.dataset.hasMore) !== 1 && button) {
                            button.style.display = 'none';
                        }
                    })
                    .catch(function () {
                        config.container.dataset.hasMore = '0';
                        if (button) {
                            button.style.display = 'none';
                        }
                    })
                    .finally(function () {
                        loading = false;
                        button?.classList.remove('events-loading');
                        requestNextPageIfNeeded();
                    });
            }

            function requestNextPageIfNeeded() {
                window.clearTimeout(scrollTimer);
                scrollTimer = window.setTimeout(function () {
                    if (window.scrollY + window.innerHeight + 300 >= document.documentElement.scrollHeight) {
                        loadEvents(activeFeedName());
                    }
                }, 80);
            }

            function activeFeedName() {
                return tabs.querySelector('#main-menu li.active')?.dataset.type || 'popular';
            }

            function bindLiveSearch(input) {
                const type = input.dataset.type || '';
                const scope = input.closest('.select-container-text');
                const dropdown = scope?.querySelector('.select-place[data-type="' + type + '"]');
                const hidden = scope?.querySelector('.id_place[data-type="' + type + '"]');
                const url = type.indexOf('search_sport') === 0
                    ? '/ajax/search_sport_types?sport_types='
                    : '/ajax/search_city?city=';
                let timer = null;
                let controller = null;

                if (!dropdown) {
                    return;
                }

                function hideDropdown() {
                    dropdown.style.display = 'none';
                }

                function render(items) {
                    dropdown.innerHTML = '';

                    items.forEach(function (item) {
                        const option = document.createElement('div');
                        option.className = 'place-item';
                        option.dataset.item = item.id;
                        option.textContent = item.name;
                        dropdown.appendChild(option);
                    });

                    dropdown.style.display = dropdown.children.length > 0 ? 'block' : 'none';
                }

                function requestSuggestions() {
                    const text = input.value.trim();

                    if (hidden) {
                        hidden.value = '';
                    }

                    window.clearTimeout(timer);
                    controller?.abort();

                    if (!text) {
                        hideDropdown();
                        return;
                    }

                    timer = window.setTimeout(function () {
                        controller = new AbortController();

                        fetch(url + encodeURIComponent(text), {
                            headers: { 'Accept': 'application/json' },
                            signal: controller.signal,
                        })
                            .then(function (response) {
                                return response.ok ? response.json() : Promise.reject(response);
                            })
                            .then(function (data) {
                                render(Array.isArray(data.item) ? data.item : []);
                            })
                            .catch(function (error) {
                                if (error.name !== 'AbortError') {
                                    hideDropdown();
                                }
                            });
                    }, 180);
                }

                input.addEventListener('input', requestSuggestions);
                input.addEventListener('focus', function () {
                    if (input.value.trim()) {
                        requestSuggestions();
                    }
                });

                dropdown.addEventListener('mousedown', function (event) {
                    const item = event.target.closest('.place-item');

                    if (!item) {
                        return;
                    }

                    event.preventDefault();
                    input.value = item.textContent || '';

                    if (hidden) {
                        hidden.value = item.dataset.item || '';
                    }

                    hideDropdown();
                });
            }

            tabs.querySelectorAll('#main-menu a').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    activateTab(link.getAttribute('href'));
                });
            });

            document.querySelectorAll('.js-events-load-more').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    loadEvents(button.dataset.feed);
                });
            });

            window.addEventListener('scroll', requestNextPageIfNeeded);

            document.querySelector('.events-search-form .lupa span')?.addEventListener('click', function (event) {
                event.preventDefault();
                event.currentTarget.closest('form')?.submit();
            });

            document.querySelectorAll('.events-search-form .text-place').forEach(bindLiveSearch);

            document.addEventListener('mousedown', function (event) {
                if (event.target.closest('.select-container-text')) {
                    return;
                }

                document.querySelectorAll('.events-search-form .select-place').forEach(function (dropdown) {
                    dropdown.style.display = 'none';
                });
            });

            activateTab('#popular');
        })();
    </script>
@endpush
