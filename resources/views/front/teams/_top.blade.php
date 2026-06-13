@php
    $membershipType = $membershipType ?? 'none';
    $leaveMessage = match ($membershipType) {
        'owner' => 'Вы владелец команды. Покинув ее, вы лишитесь прав владельца. Выйти из команды?',
        'admin' => 'Вы администратор команды. Покинув ее, вы лишитесь административных прав. Выйти из команды?',
        'invited' => 'Вы действительно хотите отказаться от приглашения?',
        'applied' => 'Вы действительно хотите отменить заявку?',
        default => 'Вы действительно хотите выйти из команды?',
    };
@endphp

<div class="relat team-profile-top" data-community-id="{{ $team->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-team-join" data-community-id="{{ $team->id }}"><span>Присоединиться</span></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button team-invite-action team-invite-accept js-team-join" data-community-id="{{ $team->id }}">Принять</a>
                <a href="#" class="groups_button team-invite-action team-invite-decline js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Приглашение отклонено">Отклонить</a>
            @elseif ($membershipType === 'owner')
                <a href="#" class="groups_button leave_fr js-team-invite" data-community-id="{{ $team->id }}">Пригласить друзей</a>
            @elseif (in_array($membershipType, ['admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-team-invite" data-community-id="{{ $team->id }}">Пригласить друзей</a>
                <a href="#" class="groups_button_leave js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <a href="#" class="groups_button applied pending js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Заявка отменена">На рассмотрении</a>
            @endif
        @endif

        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $teamData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $teamData['avatar'] }}" alt="">
        <h3 class="name">
            {{ $teamData['name'] }}
            <br>
            @if ($teamData['sport_type'])
                ({{ $teamData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $teamData['place'] }}</p>
        @if ($canManageTeam ?? false)
            <a class="button_edit_groups team-top-edit" href="{{ route('front.teams.edit', ['community' => $team->id]) }}">Редактировать</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if ($teamData['about'])
    <div class="sport_group_title">{{ $teamData['about'] }}</div>
@endif

<ul class="sport_group_list">
    <li><a href="{{ route('front.teams.show', ['community' => $team->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Лента</span></a></li>
    <li><a href="{{ route('front.teams.members', ['community' => $team->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Участники</span></a></li>
    @if ($permissions['photo'])
        <li><a href="{{ route('front.teams.photoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Фотографии</span></a></li>
    @endif
    @if ($permissions['video'])
        <li><a href="{{ route('front.teams.videoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Видео</span></a></li>
    @endif
    <li><a href="{{ route('front.teams.events', ['community' => $team->id]) }}" @class(['active-link' => $section === 'events'])><i class="icon_list icon-1"></i><span>Мероприятия</span></a></li>
</ul>

@once
    @push('styles')
        <style>
            .team-profile-top .groups_button,
            .team-profile-top .groups_button_leave {
                border: 0;
                outline: none;
            }

            .team-profile-top .groups_button {
                min-width: 180px;
                text-align: center;
            }

            .team-profile-top .team-invite-action {
                bottom: auto;
                border-radius: 8em;
                min-width: 100px;
                padding: 0 18px;
            }

            .team-profile-top .team-invite-action:after,
            .team-profile-top .groups_button.pending:after {
                content: none;
            }

            .team-profile-top .team-invite-accept {
                top: 30px;
                right: 180px;
                background: #49afa2;
            }

            .team-profile-top .team-invite-decline {
                top: 30px;
                right: 50px;
                background: #cc0000;
            }

            .team-profile-top .groups_button.pending {
                background: #f0ad4e;
                border-radius: 8em;
                padding: 0 18px;
                right: 65px;
            }

            .team-top-edit {
                display: inline-block;
                margin-left: 12px;
                margin-top: 8px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const ajaxUrl = '{{ route('front.ajax.handle', ['action' => '__ACTION__']) }}';
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                function teamNotice(text, ok) {
                    const className = ok ? 'save_window_ok' : 'save_window_fail';
                    const notice = $('<div class="' + className + ' hiden"></div>').text(text);
                    $('body').append(notice);
                    setTimeout(function () { notice.removeClass('hiden'); }, 50);
                    setTimeout(function () {
                        notice.addClass('hiden');
                        setTimeout(function () { notice.remove(); }, 900);
                    }, 1600);
                }

                function memberAjax(action, data) {
                    return $.ajax({
                        type: 'POST',
                        url: ajaxUrl.replace('__ACTION__', action),
                        data: Object.assign({_token: token}, data),
                    });
                }

                function setJoinedState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button leave_fr js-team-invite" data-community-id="' + communityId + '">Пригласить друзей</a>' +
                        '<a href="#" class="groups_button_leave js-team-leave" data-community-id="' + communityId + '" data-message="Вы действительно хотите выйти из команды?"></a>'
                    );
                }

                function setLeftState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-team-join" data-community-id="' + communityId + '"><span>Присоединиться</span></a>'
                    );
                }

                function setPendingState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button applied pending js-team-leave" data-community-id="' + communityId + '" data-message="Вы действительно хотите отменить заявку?" data-silent="1" data-success-message="Заявка отменена">На рассмотрении</a>'
                    );
                }

                $(document).on('click', '.js-team-join', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.team-profile-top');

                    memberAjax('change_member_status', {id: communityId, status: 1})
                        .done(function (response) {
                            if (response.result === 'success') {
                                if (response.member === 'applied') {
                                    setPendingState(root, communityId);
                                    teamNotice('Заявка отправлена', true);
                                } else {
                                    setJoinedState(root, communityId);
                                    teamNotice('Вы вступили в команду', true);
                                }
                            } else {
                                teamNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            teamNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-team-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.team-profile-top');
                    const message = button.data('message') || 'Вы действительно хотите выйти из команды?';
                    const silent = String(button.data('silent')) === '1';

                    if (!silent && !window.confirm(message)) {
                        return;
                    }

                    memberAjax('change_member_status', {id: communityId, status: 0})
                        .done(function (response) {
                            if (response.result === 'success') {
                                setLeftState(root, communityId);
                                teamNotice(button.data('success-message') || 'Статус обновлен', true);
                            } else {
                                teamNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            teamNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-team-invite', function (event) {
                    event.preventDefault();
                    const button = $(this);

                    memberAjax('send_community_invitation', {community_id: button.data('community-id')})
                        .done(function (response) {
                            if (response.result === 'success') {
                                teamNotice('Приглашения вашим друзьям отправлены', true);
                            } else {
                                teamNotice('Не удалось отправить приглашения', false);
                            }
                        })
                        .fail(function () {
                            teamNotice('Не удалось отправить приглашения', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
