@php
    $membershipType = $membershipType ?? 'none';
    $accessDenied = (bool) ($communityAccessDenied ?? false);
    $leaveMessage = match ($membershipType) {
        'owner' => 'You are the event owner. If you leave it, you will lose owner rights. Leave the event?',
        'admin' => 'You are an event administrator. If you leave it, you will lose administrator rights. Leave the event?',
        'invited' => 'Do you really want to decline the invitation?',
        'applied' => 'Do you really want to cancel the request?',
        default => 'Do you really want to leave the event?',
    };
@endphp

<div class="relat team-profile-top event-profile-top" data-event-id="{{ $event->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-event-join" data-event-id="{{ $event->id }}"><span>Join</span></a>
                <a href="#" class="groups_button_leave hide js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button js-event-join" data-event-id="{{ $event->id }}"><span>Accept invitation</span></a>
                <a href="#" class="groups_button_leave red js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif (in_array($membershipType, ['owner', 'admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-event-invite" data-event-id="{{ $event->id }}">Invite friends</a>
                <a href="#" class="groups_button_leave js-event-leave" data-event-id="{{ $event->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <span class="groups_button applied"><span>Request sent</span></span>
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
            <a class="button_edit_groups team-top-edit" href="{{ route('front.events.edit', ['event' => $event->id]) }}">Edit</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if (! $accessDenied && $eventData['description'])
    <div class="sport_group_title">{{ $eventData['description'] }}</div>
@endif

@if (! $accessDenied)
    <ul class="sport_group_list">
        <li><a href="{{ route('front.events.show', ['event' => $event->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Feed</span></a></li>
        <li><a href="{{ route('front.events.members', ['event' => $event->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Members</span></a></li>
        <li><a href="{{ route('front.events.photoalbums', ['event' => $event->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Photos</span></a></li>
        <li><a href="{{ route('front.events.videoalbums', ['event' => $event->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Video</span></a></li>
    </ul>
@endif

@if ($viewer)
    @include('front.events._invite-modal')
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

                function confirmEventLeave(message, action) {
                    if (typeof $.confirm !== 'function') {
                        return;
                    }

                    $.confirm({
                        title: 'Confirmation',
                        message: message,
                        buttons: {
                            'Yes': {
                                class: 'blue',
                                action: action,
                            },
                            'No': {
                                class: 'gray',
                                action: function () {
                                },
                            },
                        },
                    });
                }

                function setJoinedState(root, eventId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button leave_fr js-event-invite" data-event-id="' + eventId + '">Invite friends</a>' +
                        '<a href="#" class="groups_button_leave js-event-leave" data-event-id="' + eventId + '" data-message="Do you really want to leave the event?"></a>'
                    );
                }

                function setLeftState(root, eventId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-event-join" data-event-id="' + eventId + '"><span>Join</span></a>' +
                        '<a href="#" class="groups_button_leave hide js-event-leave" data-event-id="' + eventId + '" data-message="Do you really want to leave the event?"></a>'
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
                                eventNotice('You joined the event', true);
                            } else {
                                eventNotice('Could not change status', false);
                            }
                        })
                        .fail(function () {
                            eventNotice('Could not change status', false);
                        });
                });

                $(document).on('click', '.js-event-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const eventId = button.data('event-id');
                    const root = button.closest('.event-profile-top');
                    const message = button.data('message') || 'Do you really want to leave the event?';

                    confirmEventLeave(message, function () {
                        eventAjax('change_event_memberstatus', {event_id: eventId, status: 0})
                            .done(function (response) {
                                if (response.result === 'success') {
                                    setLeftState(root, eventId);
                                    eventNotice('Status updated', true);
                                } else {
                                    eventNotice('Could not change status', false);
                                }
                            })
                            .fail(function () {
                                eventNotice('Could not change status', false);
                            });
                    });
                });

                $(document).on('click', '.js-event-invite', function (event) {
                    event.preventDefault();
                    const button = $(this);

                    if (typeof window.openEventInviteModal === 'function') {
                        window.openEventInviteModal(button.data('event-id'));
                        return;
                    }

                    eventAjax('send_event_invitation', {event_id: button.data('event-id')})
                        .done(function (response) {
                            if (response.result === 'success') {
                                eventNotice('Invitations have been sent to your friends', true);
                            } else {
                                eventNotice('Could not send invitations', false);
                            }
                        })
                        .fail(function () {
                            eventNotice('Could not send invitations', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
