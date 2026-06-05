@php
    use App\Helpers\FrontAssets;

    $profileUrl = route('front.profile.show', ['user' => $friend->id]);
    $firstname = $friend->firstname ?: $friend->displayName();
    $lastname = $friend->firstname ? (string) $friend->lastname : '';
    $city = $friend->city;
    $messageUrl = $viewer
        ? route('front.profile.show', ['user' => $viewer->id]) . '?q=messages&sel=' . $friend->id
        : $profileUrl;
@endphp

<div class="col-xs-6 possible-friend-cart" data-num="{{ $friend->id }}">
    <a class="possible-avatar" href="{{ $profileUrl }}">
        <img src="{{ FrontAssets::userAvatar($friend) }}" alt="">
    </a>
    <a href="{{ $profileUrl }}">
        <h5>
            <strong>
                {{ $firstname }}<span class="status_user" data-num="{{ $friend->id }}"></span>
                <br>{{ $lastname }}
            </strong>
        </h5>
    </a>
    <p>{{ $city }}</p>

    @if ($showMessage ?? true)
        <a href="{{ $messageUrl }}" data-tooltip="Написать сообщение"><b></b></a>
    @endif

    @if (($action ?? null) === 'add')
        <div class="control">
            <span>
                <a onclick="add_as_friend({{ $friend->id }});" data-tooltip="Добавить в друзья">
                    <img src="{{ asset('templates/images/icon-ok.png') }}" alt="">
                </a>
            </span>
            <span>
                <img
                    src="{{ asset('templates/images/icon-krest.png') }}"
                    alt=""
                    class="js-hide-possible-friend"
                    data-num="{{ $friend->id }}"
                    data-tooltip="Больше не показывать"
                >
            </span>
        </div>
    @elseif (($action ?? null) === 'remove')
        <div class="control">
            <span></span>
            <span>
                <a onclick="remove_friend({{ $friend->id }});" data-tooltip="Удалить из друзей">
                    <img src="{{ asset('templates/images/icon-krest.png') }}" alt="">
                </a>
            </span>
        </div>
    @elseif (($action ?? null) === 'accept')
        <div class="control">
            <span>
                <a onclick="accept_friendship({{ $friend->id }});" data-tooltip="Принять заявку">
                    <img src="{{ asset('templates/images/icon-ok.png') }}" alt="">
                </a>
            </span>
            <span></span>
        </div>
    @endif
</div>
