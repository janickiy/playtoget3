<div class="event-item" id="community_{{ $group['id'] }}">
    <a href="{{ route('front.playgrounds.index') }}" class="img">
        <img border="0" src="{{ $group['avatar'] }}" alt="">
    </a>
    <div class="teg">
        <p><a href="{{ route('front.playgrounds.index') }}">{{ $group['name'] }}</a></p>
        <p>{{ $group['type_label'] }}</p>
        <p>
            @if ($group['sport_type'])
                {{ $group['sport_type'] }}<br>
            @endif
            @if ($group['status'])
                {{ $group['status'] }}<br>
            @endif
            @if ($group['place'])
                {{ $group['place'] }}
            @endif
        </p>
        <p class="group-members"><i></i>{{ $group['members_text'] }}</p>
        <div class="transparent"></div>
    </div>
</div>
