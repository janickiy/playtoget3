<div class="event-item" id="community_{{ $team['id'] }}">
    <a href="{{ route('front.teams.show', ['community' => $team['id']]) }}" class="img">
        <img border="0" src="{{ $team['avatar'] }}" alt="">
    </a>
    <div class="teg">
        <p><a href="{{ route('front.teams.show', ['community' => $team['id']]) }}">{{ $team['name'] }}</a></p>
        <p>{{ $team['type_label'] }}</p>
        <p>
            @if ($team['sport_type'])
                {{ $team['sport_type'] }}<br>
            @endif
            @if ($team['status'])
                {{ $team['status'] }}<br>
            @endif
            @if ($team['place'])
                {{ $team['place'] }}
            @endif
        </p>
        <p class="team-members"><i></i>{{ $team['members_text'] }}</p>
        @if ($team['can_edit'] ?? false)
            <a href="{{ route('front.teams.edit', ['community' => $team['id']]) }}">Редактировать</a>
        @endif
        <div class="transparent"></div>
    </div>
</div>
