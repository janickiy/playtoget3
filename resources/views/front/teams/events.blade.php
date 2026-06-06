@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @include('front.teams._top')

        <div class="photo-caption">
            <h3>Мероприятия команды</h3>
        </div>

        @if ($events->isNotEmpty())
            <div class="event-container">
                @foreach ($events as $event)
                    <div class="event-item">
                        <a href="{{ route('front.events.show', ['event' => $event['id']]) }}" class="img"><img src="{{ $event['avatar'] }}" alt="" class="marginLeft-100"></a>
                        <div class="teg">
                            <p><a href="{{ route('front.events.show', ['event' => $event['id']]) }}">{{ $event['name'] }}</a></p>
                            <p>
                                @if ($event['sport_type'])
                                    {{ $event['sport_type'] }}<br>
                                @endif
                                @if ($event['city'])
                                    {{ $event['city'] }}<br>
                                @endif
                                {{ $event['date'] }}
                            </p>
                            <p>{{ $event['description'] }}</p>
                            <p><i></i>{{ $event['participants'] }} команд</p>
                            <span @class(['ended' => ! $event['active']])>{{ $event['active'] ? 'Мероприятие продолжается' : 'Мероприятие завершено' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="photo-caption">
                <h5 class="center_text">У команды пока нет мероприятий</h5>
            </div>
        @endif
    </div>
@endsection
