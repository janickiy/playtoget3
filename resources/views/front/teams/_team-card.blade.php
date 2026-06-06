<div class="event-item">
    <a href="{{ route('front.teams.show', ['community' => $team['id']]) }}" class="img">
        <img src="{{ $team['avatar'] }}" alt="" class="marginLeft-100">
    </a>
    <div class="teg">
        <p><a href="{{ route('front.teams.show', ['community' => $team['id']]) }}">{{ $team['name'] }}</a></p>
        <p>
            @if ($team['sport_type'])
                {{ $team['sport_type'] }}<br>
            @endif
            @if ($team['place'])
                {{ $team['place'] }}<br>
            @endif
        </p>
        <p>{{ $team['about'] }}</p>
        <p><i></i>{{ $team['members_count'] }} участников</p>
    </div>
</div>
