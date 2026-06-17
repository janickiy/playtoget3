<div class="event-item">
    <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img">
        <img src="{{ $event['avatar'] ?: asset('frontend/images/noimage.png') }}" alt="" class="event-card-image" style="display:block;width:100%;height:100%;margin-left:0!important;object-fit:cover;object-position:center;" onerror="this.onerror=null;this.src='{{ asset('frontend/images/noimage.png') }}';">
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
            <a href="{{ route('front.events.edit', ['event' => $event['id']]) }}">Edit</a>
        @endif
        <span @class(['ended' => ! $event['active']])>{{ $event['status'] }}</span>
    </div>
</div>
