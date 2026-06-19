@php
    use App\Helpers\FrontAssets;

    $profileUrl = route('front.profile.show', ['user' => $friend->id]);
    $firstname = $friend->firstname ?: $friend->displayName();
    $lastname = $friend->firstname ? (string) $friend->lastname : '';
    $city = $friend->city;
    $messageUrl = $viewer
        ? route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $friend->id])
        : $profileUrl;
@endphp

<div class="col-xs-6 possible-friend-cart" data-num="{{ $friend->id }}">
    <a class="possible-avatar" href="{{ $profileUrl }}">
        <img src="{{ FrontAssets::userAvatar($friend) }}" alt="">
    </a>
    <a href="{{ $profileUrl }}">
        <h5>
            <strong>
                {{ $firstname }}
                <br>{{ $lastname }}
            </strong>
        </h5>
    </a>
    <p>{{ $city }}</p>

    @if ($showMessage ?? true)
        <a href="{{ $messageUrl }}" data-tooltip="Send message"><b></b></a>
    @endif

    @if (($action ?? null) === 'add')
        <div class="control">
            <span>
                <a onclick="add_as_friend({{ $friend->id }});" data-tooltip="Add friend">
                    <img src="{{ asset('frontend/images/icon-plus.svg') }}" alt="">
                </a>
            </span>
            <span>
                <img
                    src="{{ asset('frontend/images/icon-krest.svg') }}"
                    alt=""
                    class="js-hide-possible-friend"
                    data-num="{{ $friend->id }}"
                    data-tooltip="Hide from suggestions"
                >
            </span>
        </div>
    @elseif (($action ?? null) === 'remove')
        <div class="control">
            <span></span>
            <span>
                <a onclick="remove_friend({{ $friend->id }});" data-tooltip="Remove from friends">
                    <img src="{{ asset('frontend/images/icon-krest.svg') }}" alt="">
                </a>
            </span>
        </div>
    @elseif (($action ?? null) === 'accept')
        <div class="control">
            <span>
                <a onclick="accept_friendship({{ $friend->id }});" data-tooltip="Accept request">
                    <img src="{{ asset('frontend/images/icon-ok.svg') }}" alt="">
                </a>
            </span>
            <span></span>
        </div>
    @endif
</div>
