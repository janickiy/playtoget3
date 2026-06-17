<div class="event-item" id="community_{{ $team['id'] }}">
    <a href="{{ route('front.teams.show', ['community' => $team['id']]) }}" class="img community-card-avatar">
        <img src="{{ $team['avatar'] ?: asset('frontend/images/noimage.png') }}" alt="" onerror="this.onerror=null;this.src='{{ asset('frontend/images/noimage.png') }}';">
        @if ($team['is_closed'] ?? false)
            <span class="community-avatar-lock" aria-label="Closed team"></span>
        @endif
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
            <a href="{{ route('front.teams.edit', ['community' => $team['id']]) }}">Edit</a>
        @endif
        @if ($inviteActions ?? false)
            <div class="community-invite-actions">
                <a href="#"
                   class="community-invite-list-action community-invite-list-accept js-community-invite-list-action"
                   data-community-id="{{ $team['id'] }}"
                   data-status="1"
                   data-tooltip="Join"
                   aria-label="Join"></a>
                <a href="#"
                   class="community-invite-list-action community-invite-list-decline js-community-invite-list-action"
                   data-community-id="{{ $team['id'] }}"
                   data-status="0"
                   data-tooltip="Decline"
                   aria-label="Decline"></a>
            </div>
        @endif
        <div class="transparent"></div>
    </div>
</div>
