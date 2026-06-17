@php
    $isMine = $viewer && (int) $message['sender_id'] === (int) $viewer->id;
@endphp

@if ($isMine)
    <div id="message-{{ $message['id'] }}" class="message">
        <div class="message-account">
            <img src="{{ $message['avatar'] }}" alt="" class="img-account">
            <h5 class="name">
                <a href="{{ $message['profile_url'] }}">{{ $message['firstname'] }} {{ $message['lastname'] }}</a>
            </h5>
            <p class="data">{{ $message['created'] }}</p>
        </div>
        <p class="message-text">
            {!! $message['content'] !!}
            @if ($message['content'] !== '' && $message['image'] !== '')
                <br>
            @endif
            {!! $message['image'] !!}
        </p>
        <div class="del-message" data-item="{{ $message['id'] }}" data-tooltip="Delete message"></div>
    </div>
@else
    <div class="message-reply" id="message-{{ $message['id'] }}">
        <div class="message">
            <div class="message-account">
                <img src="{{ $message['avatar'] }}" alt="" class="img-account">
                <h5 class="name">
                    <a href="{{ $message['profile_url'] }}">{{ $message['firstname'] }} {{ $message['lastname'] }}</a>
                </h5>
                <p class="data">{{ $message['created'] }}</p>
            </div>
            <p class="message-reply-text">
                {!! $message['content'] !!}
                @if ($message['content'] !== '' && $message['image'] !== '')
                    <br>
                @endif
                {!! $message['image'] !!}
            </p>
        </div>
    </div>
@endif
