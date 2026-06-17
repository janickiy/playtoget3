@foreach ($events as $event)
    <div class="event-item" id="event_search_{{ $event['id'] }}">
        <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img">
            <img src="{{ $event['avatar'] ?: asset('frontend/images/noimage.png') }}" alt="" class="event-card-image" style="display:block;width:100%;height:100%;margin-left:0!important;object-fit:cover;object-position:center;" onerror="this.onerror=null;this.src='{{ asset('frontend/images/noimage.png') }}';">
        </a>
        <a class="addEvent" data-tooltip="Join" data-item="{{ $event['id'] }}" data-status="1">
            <img src="{{ asset('frontend/images/icon-ok.png') }}" alt="">
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
                @if ($event['date'])
                    Start: {{ $event['date'] }}<br>
                @endif
                @if ($event['date_to'])
                    End: {{ $event['date_to'] }}
                @endif
            </p>
            <p>{{ $event['description'] }}</p>
            <p><i></i>{{ $event['user_participants'] }} participants</p>
            <span @class(['ended' => ! $event['active']])>{{ $event['active'] ? 'In progress' : 'Completed' }}</span>
        </div>
    </div>
@endforeach
