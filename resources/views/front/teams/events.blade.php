@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Команда',
            'labelLower' => 'команда',
            'labelGenitive' => 'команды',
            'pluralGenitive' => 'команд',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $communityKind = $communityView['kind'];
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        @if ($canManage)
            <div class="photo-caption">
                <h3>Поиск</h3>
            </div>
            <form class="form-horizontal team-events-search-form" enctype="multipart/form-data" method="post" action="">
                <div class="form-group">
                    <div class="col-lg-12">
                        <p class="select-container-text lupa width100">
                            <input class="form-control search_events" type="text" name="name" placeholder="Начните вводить" autocomplete="off">
                            <span></span>
                        </p>
                    </div>
                </div>
            </form>
            <br>
            <div id="resultSearch"></div>
        @endif

        <div class="photo-caption">
            <h3>Мероприятия {{ $communityView['labelGenitive'] }}</h3>
        </div>

        @if ($events->isNotEmpty())
            <div class="event-container">
                @foreach ($events as $event)
                    <div class="event-item">
                        <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img"><img src="{{ $event['avatar'] }}" alt="" class="marginLeft-100"></a>
                        <div class="teg">
                            <p><a href="{{ route('front.events.show', ['event' => $event['id']]) }}">{{ $event['name'] }}</a></p>
                            <p>
                                @if ($event['sport_type'])
                                    {{ $event['sport_type'] }}<br>
                                @endif
                                @if ($event['city'])
                                    {{ $event['city'] }}<br>
                                @endif
                                {{ $event['date'] }}
                            </p>
                            <p>{{ $event['description'] }}</p>
                            <p><i></i>{{ $event['participants'] }} {{ $communityView['pluralGenitive'] }}</p>
                            <span @class(['ended' => ! $event['active']])>{{ $event['active'] ? 'Мероприятие продолжается' : 'Мероприятие завершено' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="photo-caption">
                <h5 class="center_text">У {{ $communityView['labelGenitive'] }} пока нет мероприятий</h5>
            </div>
        @endif
    </div>
@endsection

@if ($canManage)
    @push('scripts')
        <script>
            $(function () {
                const searchEndpoint = '{{ route('front.ajax.handle', ['action' => 'search_event']) }}';
                const changeEndpoint = '{{ route('front.ajax.handle', ['action' => 'change_event_community_status']) }}';
                const token = $('meta[name="csrf-token"]').attr('content') || '';
                const settings = {
                    number: 10,
                    offset: 0,
                    member_id: '{{ $community->id }}',
                    eventable_type: '{{ $communityKind }}',
                };
                const $results = $('#resultSearch');
                let timer = null;

                $('.search_events').on('keyup input', function () {
                    const value = $(this).val();

                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () {
                        $results.html('');

                        $.ajax({
                            type: 'POST',
                            url: searchEndpoint,
                            data: {
                                _token: token,
                                number: settings.number,
                                offset: settings.offset,
                                member_id: settings.member_id,
                                eventable_type: settings.eventable_type,
                                search: value,
                            },
                            success: function (data) {
                                if (data.status === 1 && data.html) {
                                    $results.html('<div class="event-container">' + data.html + '</div>');
                                } else {
                                    $results.html('<div class="photo-caption"><h5 class="center_text">Мероприятия не найдены</h5></div>');
                                }
                            }
                        });
                    }, 250);
                });

                $(document).on('click', '.addEvent', function (event) {
                    event.preventDefault();

                    const $button = $(this);

                    if ($button.data('loading')) {
                        return;
                    }

                    $button.data('loading', true);

                    $.ajax({
                        type: 'POST',
                        url: changeEndpoint,
                        data: {
                            _token: token,
                            event_id: $button.attr('data-item'),
                            community_id: settings.member_id,
                            eventable_type: settings.eventable_type,
                            status: $button.attr('data-status') || 1,
                        },
                        success: function (data) {
                            if (data.result === 'success') {
                                window.location.reload();
                            }
                        },
                        complete: function () {
                            $button.data('loading', false);
                        }
                    });
                });
            });
        </script>
    @endpush
@endif
