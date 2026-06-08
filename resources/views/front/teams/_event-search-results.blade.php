@foreach ($events as $event)
    <div class="event-item" id="event_search_{{ $event['id'] }}">
        <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img">
            <img src="{{ $event['avatar'] }}" alt="" class="marginLeft-100" onerror="this.onerror=null;this.src='{{ asset('frontend/images/noimage.png') }}';">
        </a>
        <a class="addEvent" data-tooltip="Присоединиться" data-item="{{ $event['id'] }}" data-status="1">
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
                    Начало: {{ $event['date'] }}<br>
                @endif
                @if ($event['date_to'])
                    Окончание: {{ $event['date_to'] }}
                @endif
            </p>
            <p>{{ $event['description'] }}</p>
            <p><i></i>Участвуют {{ $event['user_participants'] }} человек</p>
            <span @class(['ended' => ! $event['active']])>{{ $event['active'] ? 'Идёт' : 'Завершенно' }}</span>
        </div>
    </div>
@endforeach
