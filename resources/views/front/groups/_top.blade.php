@php
    $membershipType = $membershipType ?? 'none';
    $closedAccessDenied = (bool) ($communityAccessDenied ?? false);
    $leaveMessage = match ($membershipType) {
        'owner' => 'You are the group owner. If you leave it, you will lose owner rights. Leave the group?',
        'admin' => 'You are a group administrator. If you leave it, you will lose admin rights. Leave the group?',
        'invited' => 'Do you really want to decline the invitation?',
        'applied' => 'Do you really want to cancel the request?',
        default => 'Do you really want to leave the group?',
    };
@endphp

<div class="relat group-profile-top" data-community-id="{{ $group->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-group-join" data-community-id="{{ $group->id }}"><span>Join</span></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button group-invite-action group-invite-accept js-group-join" data-community-id="{{ $group->id }}">Accept</a>
                <a href="#" class="groups_button group-invite-action group-invite-decline js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Invitation declined">Decline</a>
            @elseif ($membershipType === 'owner')
                <a href="#" class="groups_button leave_fr js-group-invite" data-community-id="{{ $group->id }}">Invite friends</a>
            @elseif (in_array($membershipType, ['admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-group-invite" data-community-id="{{ $group->id }}">Invite friends</a>
                <a href="#" class="groups_button_leave js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <a href="#" class="groups_button applied pending js-group-leave" data-community-id="{{ $group->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Request cancelled">Pending</a>
            @endif
        @endif

        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $groupData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img src="{{ $groupData['avatar'] }}" alt="">
        @if ($groupData['is_closed'] ?? false)
            <span class="community-avatar-lock community-avatar-lock--top" aria-label="Closed group"></span>
        @endif
        <h3 class="name">
            {{ $groupData['name'] }}
            <br>
            @if ($groupData['sport_type'])
                ({{ $groupData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $groupData['place'] }}</p>
        @if ($canManageGroup ?? false)
            <a class="button_edit_groups group-top-edit" href="{{ route('front.groups.edit', ['community' => $group->id]) }}">Edit</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if (! $closedAccessDenied && $groupData['about'])
    <div class="sport_group_title">{{ $groupData['about'] }}</div>
@endif

@if (! $closedAccessDenied)
    <ul class="sport_group_list">
        <li><a href="{{ route('front.groups.show', ['community' => $group->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Feed</span></a></li>
        <li><a href="{{ route('front.groups.members', ['community' => $group->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Members</span></a></li>
        @if ($permissions['photo'])
            <li><a href="{{ route('front.groups.photoalbums', ['community' => $group->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Photos</span></a></li>
        @endif
        @if ($permissions['video'])
            <li><a href="{{ route('front.groups.videoalbums', ['community' => $group->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Video</span></a></li>
        @endif
        <li><a href="{{ route('front.groups.events', ['community' => $group->id]) }}" @class(['active-link' => $section === 'events'])><i class="icon_list icon-1"></i><span>Events</span></a></li>
    </ul>
@endif

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

                function confirmGroupLeave(message, action) {
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

                function setJoinedState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button leave_fr js-group-invite" data-community-id="' + communityId + '">Invite friends</a>' +
                        '<a href="#" class="groups_button_leave js-group-leave" data-community-id="' + communityId + '" data-message="Do you really want to leave the group?"></a>'
                    );
                }

                function setLeftState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-group-join" data-community-id="' + communityId + '"><span>Join</span></a>'
                    );
                }

                function setPendingState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button applied pending js-group-leave" data-community-id="' + communityId + '" data-message="Do you really want to cancel the request?" data-silent="1" data-success-message="Request cancelled">Pending</a>'
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
                                    groupNotice('Request sent', true);
                                } else {
                                    setJoinedState(root, communityId);
                                    groupNotice('You joined the group', true);
                                }
                            } else {
                                groupNotice('Could not change status', false);
                            }
                        })
                        .fail(function () {
                            groupNotice('Could not change status', false);
                        });
                });

                $(document).on('click', '.js-group-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.group-profile-top');
                    const message = button.data('message') || 'Do you really want to leave the group?';
                    const silent = String(button.data('silent')) === '1';

                    const leaveAction = function () {
                        memberAjax('change_member_status', {id: communityId, status: 0})
                            .done(function (response) {
                                if (response.result === 'success') {
                                    setLeftState(root, communityId);
                                    groupNotice(button.data('success-message') || 'Status updated', true);
                                } else {
                                    groupNotice('Could not change status', false);
                                }
                            })
                            .fail(function () {
                                groupNotice('Could not change status', false);
                            });
                    };

                    if (silent) {
                        leaveAction();
                        return;
                    }

                    confirmGroupLeave(message, leaveAction);
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
                                groupNotice('Invitations have been sent to your friends', true);
                            } else {
                                groupNotice('Could not send invitations', false);
                            }
                        })
                        .fail(function () {
                            groupNotice('Could not send invitations', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
