@php
    $isOwnPage = $viewer && (int) $viewer->id === (int) $profileUser->id;
    $showProfileActions = $viewer && ! $isOwnPage && ! $profileUser->isDeleted() && $friendshipStatus !== 'blocked_by_user';
    $showProfileIdentity = ! $profileUser->isDeleted();
@endphp

<div class="relat">
    <div class="cover_page">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $profileData['cover'] }}" alt="">
        </div>

        @if ($showProfileActions)
            <div class="cover-buttons">
                @if ($permissions['send_message'])
                    <a class="cover-send-message" href="{{ route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $profileUser->id]) }}">
                        <button class="btn btn-primary">Send <span>message</span></button>
                    </a>
                @endif

                <div id="friends_button">
                    @if ($friendshipStatus === 'invitation_sent')
                        <button type="button" class="btn btn-primary"><span>Invitation </span>sent</button>
                    @elseif ($friendshipStatus === 'friend')
                        <button type="button" class="btn btn-danger" id="remove_friend" data-item="{{ $profileUser->id }}">Remove<span> friend</span></button>
                    @elseif ($friendshipStatus === 'nofriend')
                        <button type="button" class="btn btn-success" id="add_as_friend" data-item="{{ $profileUser->id }}">Add<span> friend</span></button>
                    @elseif ($friendshipStatus === 'invated')
                        <button type="button" class="btn btn-success" id="accept_friendship" data-item="{{ $profileUser->id }}">Accept<span> friendship</span></button>
                    @endif
                </div>

                <div id="block_user_button">
                    @if ($friendshipStatus === 'block')
                        <button type="button" class="btn btn-danger" id="unblock_user" data-item="{{ $profileUser->id }}">Unblock</button>
                    @else
                        <button type="button" class="btn btn-danger" id="block_user" data-item="{{ $profileUser->id }}">Block</button>
                    @endif
                </div>
                <div class="clearfix"></div>
            </div>
        @endif
    </div>

    <div class="clearfix"></div>

    <div id="top-top" class="account top_thumb_avatar">
        <span class="avatar-status-holder avatar-status-holder--profile">
            <img src="{{ $profileData['avatar'] }}" alt="">
            @include('front.partials.user-online-status', [
                'isOnline' => $profileData['is_online'],
                'userId' => $profileUser->id,
            ])
        </span>
        @if ($showProfileIdentity)
            <h3 class="name">
                {{ $profileData['firstname'] }}<br>
                {{ $profileData['lastname'] }}
                @if ($profileData['nickname'] !== '')
                    <br>({{ $profileData['nickname'] }})
                @endif
            </h3>
            <p class="citation">{{ $profileData['about'] }}</p>
        @endif
    </div>
</div>
<div class="clearfix"></div>

@if (! $profileUser->isDeleted() && ($permissions['profile'] ?? true) && ! ($permissions['blocked_by_profile'] ?? false))
    @include('front.profile._information')
@endif
