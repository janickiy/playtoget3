@php
    $isOwnPage = $viewer && (int) $viewer->id === (int) $profileUser->id;
    $showProfileActions = $viewer && ! $isOwnPage && $friendshipStatus !== 'blocked_by_user';
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
                    <a class="cover-send-message" href="{{ route('front.profile.show', ['user' => $viewer->id]) }}?q=messages&sel={{ $profileUser->id }}">
                        <button class="btn btn-primary">Написать <span>сообщение</span></button>
                    </a>
                @endif

                <div id="friends_button">
                    @if ($friendshipStatus === 'invitation_sent')
                        <button class="btn btn-primary"><span>Приглашение </span>отправлено</button>
                    @elseif ($friendshipStatus === 'friend')
                        <button class="btn btn-danger" id="remove_friend" onclick="remove_friend({{ $profileUser->id }})">Удалить<span> друга</span></button>
                    @elseif ($friendshipStatus === 'nofriend')
                        <button class="btn btn-success" id="add_as_friend" onclick="add_as_friend({{ $profileUser->id }})">Добавить<span> друга</span></button>
                    @elseif ($friendshipStatus === 'invated')
                        <button class="btn btn-success" id="accept_friendship" onclick="accept_friendship({{ $profileUser->id }})">Принять<span> дружбу</span></button>
                    @endif
                </div>

                <div id="block_user_button">
                    @if ($friendshipStatus === 'block')
                        <button class="btn btn-danger" id="unblock_user" data-item="{{ $profileUser->id }}">Разблокировать</button>
                    @else
                        <button class="btn btn-danger" id="block_user" data-item="{{ $profileUser->id }}">Заблокировать</button>
                    @endif
                </div>
                <div class="clearfix"></div>
            </div>
        @endif
    </div>

    <div class="clearfix"></div>

    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $profileData['avatar'] }}" alt="">
        <h3 class="name">
            {{ $profileData['firstname'] }}
            <span class="status_user{{ $profileData['is_online'] ? ' online' : '' }}" data-num="{{ $profileUser->id }}"></span><br>
            {{ $profileData['lastname'] }}
            @if ($profileData['secondname'] !== '')
                <br>({{ $profileData['secondname'] }})
            @endif
        </h3>
        <p class="citation">{{ $profileData['about'] }}</p>
    </div>
</div>
<div class="clearfix"></div>

@include('front.profile._information')
