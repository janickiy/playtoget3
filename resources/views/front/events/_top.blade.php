@php
    $membershipType = $membershipType ?? 'none';
    $accessDenied = (bool) ($communityAccessDenied ?? false);
    $leaveMessage = match ($membershipType) {
        'owner' => 'Вы владелец мероприятия. Покинув его, вы лишитесь прав владельца. Покинуть мероприятие?',
        'admin' => 'Вы администратор мероприятия. Покинув его, вы лишитесь административных прав. Покинуть мероприятие?',
        'invited' => 'Вы действительно хотите отказаться от приглашения?',
        'applied' => 'Вы действительно хотите отменить заявку?',
        default => 'Вы действительно хотите покинуть мероприятие?',
    };
@endphp

<div class="relat team-profile-top event-profile-top" data-event-id="{{ $event->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-event-join" data-event-id="{{ $event->id }}"><span>Присоединиться</span></a>
                <a href="#" class="groups_button_leave hide js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button js-event-join" data-event-id="{{ $event->id }}"><span>Принять приглашение</span></a>
                <a href="#" class="groups_button_leave red js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif (in_array($membershipType, ['owner', 'admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-event-invite" data-event-id="{{ $event->id }}">Пригласить друзей</a>
                <a href="#" class="groups_button_leave js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <span class="groups_button applied"><span>Вы отправили заявку</span></span>
                <a href="#" class="groups_button_leave js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @endif
        @endif

        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $eventData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img src="{{ $eventData['cover'] }}" alt="">
        <h3 class="name">
            {{ $eventData['name'] }}
            <br>
            @if ($eventData['sport_type'])
                ({{ $eventData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ trim($eventData['place'] . ' ' . $eventData['address']) }}</p>
        @if ($canManageEvent ?? false)
            <a class="button_edit_groups team-top-edit" href="{{ route('front.events.edit', ['event' => $event->id]) }}">Редактировать</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if (! $accessDenied && $eventData['description'])
    <div class="sport_group_title">{{ $eventData['description'] }}</div>
@endif

@if (! $accessDenied)
    <ul class="sport_group_list">
        <li><a href="{{ route('front.events.show', ['event' => $event->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Лента</span></a></li>
        <li><a href="{{ route('front.events.members', ['event' => $event->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Участники</span></a></li>
        <li><a href="{{ route('front.events.photoalbums', ['event' => $event->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Фотографии</span></a></li>
        <li><a href="{{ route('front.events.videoalbums', ['event' => $event->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Видео</span></a></li>
    </ul>
@endif

@once
    @push('scripts')
        <script>
            (function () {
                const ajaxUrl = '{{ route('front.ajax.handle', ['action' => '__ACTION__']) }}';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function eventNotice(text, ok) {
                    const className = ok ? 'save_window_ok' : 'save_window_fail';
                    const notice = $('<div class="' + className + ' hiden"></div>').text(text);
                    $('body').append(notice);
                    setTimeout(function () { notice.removeClass('hiden'); }, 50);
                    setTimeout(function () {
                        notice.addClass('hiden');
                        setTimeout(function () { notice.remove(); }, 900);
                    }, 1600);
                }

                function eventAjax(action, data) {
                    return $.ajax({
                        type: 'POST',
                        url: ajaxUrl.replace('__ACTION__', action),
                        data: Object.assign({_token: token}, data),
                    });
                }

                function setJoinedState(root, eventId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button leave_fr js-event-invite" data-event-id="' + eventId + '">Пригласить друзей</a>' +
                        '<a href="#" class="groups_button_leave js-event-leave" data-event-id="' + eventId + '" data-message="Вы действительно хотите покинуть мероприятие?"></a>'
                    );
                }

                function setLeftState(root, eventId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-event-join" data-event-id="' + eventId + '"><span>Присоединиться</span></a>' +
                        '<a href="#" class="groups_button_leave hide js-event-leave" data-event-id="' + eventId + '" data-message="Вы действительно хотите покинуть мероприятие?"></a>'
                    );
                }

                $(document).on('click', '.js-event-join', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const eventId = button.data('event-id');
                    const root = button.closest('.event-profile-top');

                    eventAjax('change_event_memberstatus', {event_id: eventId, status: 1})
                        .done(function (response) {
                            if (response.result === 'success') {
                                setJoinedState(root, eventId);
                                eventNotice('Вы присоединились к мероприятию', true);
                            } else {
                                eventNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            eventNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-event-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const eventId = button.data('event-id');
                    const root = button.closest('.event-profile-top');
                    const message = button.data('message') || 'Вы действительно хотите покинуть мероприятие?';

                    if (!window.confirm(message)) {
                        return;
                    }

                    eventAjax('change_event_memberstatus', {event_id: eventId, status: 0})
                        .done(function (response) {
                            if (response.result === 'success') {
                                setLeftState(root, eventId);
                                eventNotice('Статус обновлен', true);
                            } else {
                                eventNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            eventNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-event-invite', function (event) {
                    event.preventDefault();
                    const button = $(this);

                    eventAjax('send_event_invitation', {event_id: button.data('event-id')})
                        .done(function (response) {
                            if (response.result === 'success') {
                                eventNotice('Приглашения вашим друзьям отправлены', true);
                            } else {
                                eventNotice('Не удалось отправить приглашения', false);
                            }
                        })
                        .fail(function () {
                            eventNotice('Не удалось отправить приглашения', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
