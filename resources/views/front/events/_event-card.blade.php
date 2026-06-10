<div class="event-item">
    <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img">
        <img src="{{ $event['avatar'] }}" alt="" class="marginLeft-100">
    </a>
    <div class="teg">
        <p><a href="{{ route('front.events.show', ['event' => $event['id']]) }}">{{ $event['name'] }}</a></p>
        <p>
            @if ($event['sport_type'])
                {{ $event['sport_type'] }}<br>
            @endif
            @if ($event['city'])
                {{ $event['city'] }}<br>
            @endif
            @if ($event['date_from'])
                {{ $event['date_from'] }}<br>
            @endif
            @if ($event['date_to'])
                {{ $event['date_to'] }}<br>
            @endif
        </p>
        @if ($event['role'])
            <p>{{ $event['role'] }}</p>
        @endif
        <p class="event-members"><i></i>{{ $event['participants'] }}</p>
        @if ($event['can_edit'])
            <a href="{{ route('front.events.edit', ['event' => $event['id']]) }}">Редактировать</a>
        @endif
        <span @class(['ended' => ! $event['active']])>{{ $event['status'] }}</span>
    </div>
</div>
