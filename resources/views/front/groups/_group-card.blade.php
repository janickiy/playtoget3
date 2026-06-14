<div class="event-item" id="community_{{ $group['id'] }}">
    <a href="{{ route('front.groups.show', ['community' => $group['id']]) }}" class="img">
        <img border="0" src="{{ $group['avatar'] ?: asset('frontend/images/noimage.png') }}" alt="" onerror="this.onerror=null;this.src='{{ asset('frontend/images/noimage.png') }}';">
    </a>
    <div class="teg">
        <p><a href="{{ route('front.groups.show', ['community' => $group['id']]) }}">{{ $group['name'] }}</a></p>
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
        @if ($group['can_edit'] ?? false)
            <a href="{{ route('front.groups.edit', ['community' => $group['id']]) }}">Редактировать</a>
        @endif
        @if ($inviteActions ?? false)
            <div class="community-invite-actions">
                <a href="#"
                   class="community-invite-list-action community-invite-list-accept js-community-invite-list-action"
                   data-community-id="{{ $group['id'] }}"
                   data-status="1"
                   data-tooltip="Вступить"
                   aria-label="Вступить"></a>
                <a href="#"
                   class="community-invite-list-action community-invite-list-decline js-community-invite-list-action"
                   data-community-id="{{ $group['id'] }}"
                   data-status="0"
                   data-tooltip="Отклонить"
                   aria-label="Отклонить"></a>
            </div>
        @endif
        <div class="transparent"></div>
    </div>
</div>
