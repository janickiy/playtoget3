@extends('front.layouts.app')

@section('content')
    @php
        $canManageCommunityMembers = (bool) ($canManageTeam ?? false);
        $viewerRole = (int) ($role ?? 0);
    @endphp
    <div class="content-groups friends">
        @include('front.teams._top')

        @if ($communityAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $communityAccessMessage])
        @else
            <div class="photo-caption">
                <h3>Members<sup>{{ $members->count() }}</sup></h3>
            </div>

            @if ($members->isNotEmpty())
                <div class="possible-friend">
                    @foreach ($members as $member)
                    @php
                        $canAffectMember = $canManageCommunityMembers
                            && $viewer
                            && (int) $member['id'] !== (int) $viewer->id
                            && (int) $member['role'] !== 1
                            && (
                                ($viewerRole === 1 && in_array((int) $member['role'], [2, 3], true))
                                || ($viewerRole === 2 && (int) $member['role'] === 3)
                            );
                        $canAddFriend = $viewer
                            && (int) $member['id'] !== (int) $viewer->id
                            && ($member['friendship_status'] ?? '') === 'nofriend';
                    @endphp
                    <div class="col-xs-6 possible-friend-cart" data-user-id="{{ $member['id'] }}">
                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                            <img src="{{ $member['avatar'] }}" alt="">
                        </a>
                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                            <h5><strong>{{ $member['firstname'] }}<br>{{ $member['lastname'] }}</strong></h5>
                        </a>
                        <p>{{ $member['city'] }}</p>
                        <p>{{ $member['role_name'] }}</p>
                        @if ($viewer)
                            <a href="{{ route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $member['id']]) }}"><b></b></a><br>
                        @endif
                        @if ($canAddFriend || $canAffectMember)
                            <div class="control community-member-control">
                                @if ($canAddFriend)
                                    <span>
                                        <a href="#"
                                           class="community-member-icon-action js-add-as-friend"
                                           data-user-id="{{ $member['id'] }}"
                                           data-tooltip="Add friend">
                                            <img src="{{ asset('frontend/images/icon-plus.svg') }}" alt="">
                                        </a>
                                    </span>
                                @endif
                                @if ($canAffectMember)
                                    <span>
                                        <a href="#"
                                           class="community-member-icon-action js-community-member-action"
                                           data-action="remove_community_member"
                                           data-community-id="{{ $team->id }}"
                                           data-user-id="{{ $member['id'] }}"
                                           data-confirm="Remove the member from the team?"
                                           data-success="Member removed"
                                           data-tooltip="Remove member">
                                            <img src="{{ asset('frontend/images/icon-krest.svg') }}" alt="">
                                        </a>
                                    </span>
                                    <span>
                                        <a href="#"
                                           class="community-member-icon-action js-community-member-action"
                                           data-action="block_community_member"
                                           data-community-id="{{ $team->id }}"
                                           data-user-id="{{ $member['id'] }}"
                                           data-confirm="Block the member in the team?"
                                           data-success="Member blocked"
                                           data-tooltip="Block member">
                                            <img src="{{ asset('frontend/images/icon-block-member.svg') }}" alt="">
                                        </a>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @else
                <p class="no_message">No members yet.</p>
            @endif

            @if ($applications->isNotEmpty())
                <div class="photo-caption">
                    <h3>Requests</h3>
                </div>
                <div class="possible-friend">
                    @foreach ($applications as $member)
                        <div class="col-xs-6 possible-friend-cart">
                            <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                            <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                            <p>{{ $member['city'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($canManageCommunityMembers)
                @include('front.communities._manage-assets')
            @endif
        @endif
    </div>
@endsection
