@php
    $membershipType = $membershipType ?? 'none';
    $closedAccessDenied = (bool) ($communityAccessDenied ?? false);
    $leaveMessage = match ($membershipType) {
        'owner' => 'You are the team owner. If you leave it, you will lose owner rights. Leave the team?',
        'admin' => 'You are a team administrator. If you leave it, you will lose admin rights. Leave the team?',
        'invited' => 'Do you really want to decline the invitation?',
        'applied' => 'Do you really want to cancel the request?',
        default => 'Do you really want to leave the team?',
    };
@endphp

<div class="relat team-profile-top" data-community-id="{{ $team->id }}">
    <div class="cover_page">
        @if ($viewer && $membershipType !== 'blocked')
            @if ($membershipType === 'none')
                <a href="#" class="groups_button js-team-join" data-community-id="{{ $team->id }}"><span>Join</span></a>
            @elseif ($membershipType === 'invited')
                <a href="#" class="groups_button team-invite-action team-invite-accept js-team-join" data-community-id="{{ $team->id }}">Accept</a>
                <a href="#" class="groups_button team-invite-action team-invite-decline js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Invitation declined">Decline</a>
            @elseif ($membershipType === 'owner')
                <a href="#" class="groups_button leave_fr js-team-invite" data-community-id="{{ $team->id }}">Invite friends</a>
            @elseif (in_array($membershipType, ['admin', 'member'], true))
                <a href="#" class="groups_button leave_fr js-team-invite" data-community-id="{{ $team->id }}">Invite friends</a>
                <a href="#" class="groups_button_leave js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}"></a>
            @elseif ($membershipType === 'applied')
                <a href="#" class="groups_button applied pending js-team-leave" data-community-id="{{ $team->id }}" data-message="{{ $leaveMessage }}" data-silent="1" data-success-message="Request cancelled">Pending</a>
            @endif
        @endif

        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $teamData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img src="{{ $teamData['avatar'] }}" alt="">
        @if ($teamData['is_closed'] ?? false)
            <span class="community-avatar-lock community-avatar-lock--top" aria-label="Closed team"></span>
        @endif
        <h3 class="name">
            {{ $teamData['name'] }}
            <br>
            @if ($teamData['sport_type'])
                ({{ $teamData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $teamData['place'] }}</p>
        @if ($canManageTeam ?? false)
            <a class="button_edit_groups team-top-edit" href="{{ route('front.teams.edit', ['community' => $team->id]) }}">Edit</a>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if (! $closedAccessDenied && $teamData['about'])
    <div class="sport_group_title">{{ $teamData['about'] }}</div>
@endif

@if (! $closedAccessDenied)
    <ul class="sport_group_list">
        <li><a href="{{ route('front.teams.show', ['community' => $team->id]) }}" @class(['active-link' => $section === 'feed'])><i class="icon_list icon-4"></i><span>Feed</span></a></li>
        <li><a href="{{ route('front.teams.members', ['community' => $team->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Members</span></a></li>
        @if ($permissions['photo'])
            <li><a href="{{ route('front.teams.photoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Photos</span></a></li>
        @endif
        @if ($permissions['video'])
            <li><a href="{{ route('front.teams.videoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Video</span></a></li>
        @endif
        <li><a href="{{ route('front.teams.events', ['community' => $team->id]) }}" @class(['active-link' => $section === 'events'])><i class="icon_list icon-1"></i><span>Events</span></a></li>
    </ul>
@endif

@if ($viewer)
    @include('front.communities._invite-modal')
@endif

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

                function confirmTeamLeave(message, action) {
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
                        '<a href="#" class="groups_button leave_fr js-team-invite" data-community-id="' + communityId + '">Invite friends</a>' +
                        '<a href="#" class="groups_button_leave js-team-leave" data-community-id="' + communityId + '" data-message="Do you really want to leave the team?"></a>'
                    );
                }

                function setLeftState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button js-team-join" data-community-id="' + communityId + '"><span>Join</span></a>'
                    );
                }

                function setPendingState(root, communityId) {
                    root.find('.groups_button').remove();
                    root.find('.groups_button_leave').remove();
                    root.find('.cover_page').prepend(
                        '<a href="#" class="groups_button applied pending js-team-leave" data-community-id="' + communityId + '" data-message="Do you really want to cancel the request?" data-silent="1" data-success-message="Request cancelled">Pending</a>'
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
                                    teamNotice('Request sent', true);
                                } else {
                                    setJoinedState(root, communityId);
                                    teamNotice('You joined the team', true);
                                }
                            } else {
                                teamNotice('Could not change status', false);
                            }
                        })
                        .fail(function () {
                            teamNotice('Could not change status', false);
                        });
                });

                $(document).on('click', '.js-team-leave', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const communityId = button.data('community-id');
                    const root = button.closest('.team-profile-top');
                    const message = button.data('message') || 'Do you really want to leave the team?';
                    const silent = String(button.data('silent')) === '1';

                    const leaveAction = function () {
                        memberAjax('change_member_status', {id: communityId, status: 0})
                            .done(function (response) {
                                if (response.result === 'success') {
                                    setLeftState(root, communityId);
                                    teamNotice(button.data('success-message') || 'Status updated', true);
                                } else {
                                    teamNotice('Could not change status', false);
                                }
                            })
                            .fail(function () {
                                teamNotice('Could not change status', false);
                            });
                    };

                    if (silent) {
                        leaveAction();
                        return;
                    }

                    confirmTeamLeave(message, leaveAction);
                });

                $(document).on('click', '.js-team-invite', function (event) {
                    event.preventDefault();
                    const button = $(this);

                    if (window.openCommunityInviteModal) {
                        window.openCommunityInviteModal(button.data('community-id'));
                        return;
                    }

                    memberAjax('send_community_invitation', {community_id: button.data('community-id')})
                        .done(function (response) {
                            if (response.result === 'success') {
                                teamNotice('Invitations have been sent to your friends', true);
                            } else {
                                teamNotice('Could not send invitations', false);
                            }
                        })
                        .fail(function () {
                            teamNotice('Could not send invitations', false);
                        });
                });
            })();
        </script>
    @endpush
@endonce
