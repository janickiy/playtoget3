@php
    $membershipType = $membershipType ?? 'none';
    $leaveMessage = match ($membershipType) {
        'owner' => 'Вы владелец группы. Покинув ее, вы лишитесь прав владельца. Выйти из группы?',
        'admin' => 'Вы администратор группы. Покинув ее, вы лишитесь административных прав. Выйти из группы?',
        'invited' => 'Вы действительно хотите отказаться от приглашения?',
        'applied' => 'Вы действительно хотите отменить заявку?',
        default => 'Вы действительно хотите выйти из группы?',
    };
@endphp

<div class="relat group-profile-top" data-community-id="{{ $group->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-group-join" data-community-id="{{ $group->id }}"><span>Присоединиться</span></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button group-invite-action group-invite-accept js-group-join" data-community-id="{{ $group->id }}">Принять</a>
                <a href="#" class="groups_button group-invite-action group-invite-decline js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Приглашение отклонено">Отклонить</a>
            @elseif ($membershipType === 'owner')
                <a href="#" class="groups_button leave_fr js-group-invite" data-community-id="{{ $group->id }}">Пригласить друзей</a>
            @elseif (in_array($membershipType, ['admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-group-invite" data-community-id="{{ $group->id }}">Пригласить друзей</a>
                <a href="#" class="groups_button_leave js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <a href="#" class="groups_button applied pending js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Заявка отменена">На рассмотрении</a>
            @endif
        @endif

        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $groupData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $groupData['avatar'] }}" alt="">
        <h3 class="name">
            {{ $groupData['name'] }}
            <br>
            @if ($groupData['sport_type'])
                ({{ $groupData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $groupData['place'] }}</p>
        @if ($canManageGroup ?? false)
            <a class="button_edit_groups group-top-edit" href="{{ route('front.groups.edit', ['community' => $group->id]) }}">Редактировать</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if ($groupData['about'])
    <div class="sport_group_title">{{ $groupData['about'] }}</div>
@endif

<ul class="sport_group_list">
    <li><a href="{{ route('front.groups.show', ['community' => $group->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Лента</span></a></li>
    <li><a href="{{ route('front.groups.members', ['community' => $group->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Участники</span></a></li>
    @if ($permissions['photo'])
        <li><a href="{{ route('front.groups.photoalbums', ['community' => $group->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Фотографии</span></a></li>
    @endif
    @if ($permissions['video'])
        <li><a href="{{ route('front.groups.videoalbums', ['community' => $group->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Видео</span></a></li>
    @endif
    <li><a href="{{ route('front.groups.events', ['community' => $group->id]) }}" @class(['active-link' => $section === 'events'])><i class="icon_list icon-1"></i><span>Мероприятия</span></a></li>
</ul>

@if ($viewer)
    @include('front.communities._invite-modal')
@endif

@once
    @push('styles')
        <style>
            .group-profile-top .groups_button,
            .group-profile-top .groups_button_leave {
                border: 0;
                outline: none;
            }

            .group-profile-top .groups_button {
                min-width: 180px;
                text-align: center;
            }

            .group-profile-top .group-invite-action {
                bottom: auto;
                border-radius: 8em;
                min-width: 100px;
                padding: 0 18px;
            }

            .group-profile-top .group-invite-action:after,
            .group-profile-top .groups_button.pending:after {
                content: none;
            }

            .group-profile-top .group-invite-accept {
                top: 30px;
                right: 180px;
                background: #49afa2;
            }

            .group-profile-top .group-invite-decline {
                top: 30px;
                right: 50px;
                background: #cc0000;
            }

            .group-profile-top .groups_button.pending {
                background: #f0ad4e;
                border-radius: 8em;
                padding: 0 18px;
                right: 65px;
            }

            .group-top-edit {
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

                function groupNotice(text, ok) {
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
                        '<a href="#" class="groups_button leave_fr js-group-invite" data-community-id="' + communityId + '">Пригласить друзей</a>' +
                        '<a href="#" class="groups_button_leave js-group-leave" data-community-id="' + communityId + '" data-message="Вы действительно хотите выйти из группы?"></a>'
                    );
                }

                function setLeftState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-group-join" data-community-id="' + communityId + '"><span>Присоединиться</span></a>'
                    );
                }

                function setPendingState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button applied pending js-group-leave" data-community-id="' + communityId + '" data-message="Вы действительно хотите отменить заявку?" data-silent="1" data-success-message="Заявка отменена">На рассмотрении</a>'
                    );
                }

                $(document).on('click', '.js-group-join', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.group-profile-top');

                    memberAjax('change_member_status', {id: communityId, status: 1})
                        .done(function (response) {
                            if (response.result === 'success') {
                                if (response.member === 'applied') {
                                    setPendingState(root, communityId);
                                    groupNotice('Заявка отправлена', true);
                                } else {
                                    setJoinedState(root, communityId);
                                    groupNotice('Вы вступили в группу', true);
                                }
                            } else {
                                groupNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            groupNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-group-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.group-profile-top');
                    const message = button.data('message') || 'Вы действительно хотите выйти из группы?';
                    const silent = String(button.data('silent')) === '1';

                    if (!silent && !window.confirm(message)) {
                        return;
                    }

                    memberAjax('change_member_status', {id: communityId, status: 0})
                        .done(function (response) {
                            if (response.result === 'success') {
                                setLeftState(root, communityId);
                                groupNotice(button.data('success-message') || 'Статус обновлен', true);
                            } else {
                                groupNotice('Не удалось изменить статус', false);
                            }
                        })
                        .fail(function () {
                            groupNotice('Не удалось изменить статус', false);
                        });
                });

                $(document).on('click', '.js-group-invite', function (event) {
                    event.preventDefault();
                    const button = $(this);

                    if (window.openCommunityInviteModal) {
                        window.openCommunityInviteModal(button.data('community-id'));
                        return;
                    }

                    memberAjax('send_community_invitation', {community_id: button.data('community-id')})
                        .done(function (response) {
                            if (response.result === 'success') {
                                groupNotice('Приглашения вашим друзьям отправлены', true);
                            } else {
                                groupNotice('Не удалось отправить приглашения', false);
                            }
                        })
                        .fail(function () {
                            groupNotice('Не удалось отправить приглашения', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
