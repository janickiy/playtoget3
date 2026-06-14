@extends('front.layouts.app')

@section('content')
    @php
        $canManageCommunityMembers = (bool) ($canManageGroup ?? false);
        $viewerRole = (int) ($role ?? 0);
    @endphp
    <div class="content-groups friends">
        @include('front.groups._top')

        <div class="photo-caption">
            <h3>Участники<sup>{{ $members->count() }}</sup></h3>
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
                    @endphp
                    <div class="col-xs-6 possible-friend-cart" data-user-id="{{ $member['id'] }}">
                        <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                            <img src="{{ $member['avatar'] }}" alt="">
                        </a>
                        <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                            <h5><strong>{{ $member['firstname'] }}<span class="status_user{{ $member['is_online'] ? ' online' : '' }}" data-num="{{ $member['id'] }}"></span><br>{{ $member['lastname'] }}</strong></h5>
                        </a>
                        <p>{{ $member['city'] }}</p>
                        <p>{{ $member['role_name'] }}</p>
                        @if ($viewer)
                            <a href="{{ route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $member['id']]) }}"><b></b></a><br>
                        @endif
                        @if ($canAffectMember)
                            <div class="control community-member-control">
                                <span>
                                    <a href="#"
                                       class="community-member-icon-action js-community-member-action"
                                       data-action="remove_community_member"
                                       data-community-id="{{ $group->id }}"
                                       data-user-id="{{ $member['id'] }}"
                                       data-confirm="Удалить участника из группы?"
                                       data-success="Участник удален"
                                       data-tooltip="Удалить участника">
                                        <img src="{{ asset('frontend/images/icon-krest.png') }}" alt="">
                                    </a>
                                </span>
                                <span>
                                    <a href="#"
                                       class="community-member-icon-action js-community-member-action"
                                       data-action="block_community_member"
                                       data-community-id="{{ $group->id }}"
                                       data-user-id="{{ $member['id'] }}"
                                       data-confirm="Заблокировать участника в группе?"
                                       data-success="Участник заблокирован"
                                       data-tooltip="Заблокировать участника">
                                        <img src="{{ asset('frontend/images/icon-block-member.svg') }}" alt="">
                                    </a>
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="no_message">Участников пока нет.</p>
        @endif

        @if ($applications->isNotEmpty())
            <div class="photo-caption">
                <h3>Заявки</h3>
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
    </div>
@endsection
