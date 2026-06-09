@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        <div class="photo-caption">
            <h3>Мероприятия</h3>
        </div>

        @if ($viewer ?? auth()->guard('web')->user())
            <div class="add-photos-album">
                <span><i></i><a href="{{ route('front.events.create') }}">Создать мероприятие</a></span>
            </div>
        @endif

        @if ($events->isNotEmpty())
            <div class="event-container">
                @foreach ($events as $event)
                    <div class="event-item">
                        <a href="{{ route('front.events.show', ['event' => $event->id]) }}" class="img">
                            <img src="{{ \App\Helpers\FrontAssets::eventCover($event) }}" alt="" class="marginLeft-100">
                        </a>
                        <div class="teg">
                            <p><a href="{{ route('front.events.show', ['event' => $event->id]) }}">{{ $event->name }}</a></p>
                            <p>
                                @if ($event->sport_type)
                                    {{ $event->sport_type }}<br>
                                @endif
                                @if ($event->place)
                                    {{ $event->place }}<br>
                                @endif
                                {{ $event->date_from?->format('d.m.Y H:i') }}
                            </p>
                            <p>{{ $event->description }}</p>
                            <span @class(['ended' => $event->date_to && $event->date_to->isPast()])>
                                {{ ! $event->date_to || $event->date_to->isFuture() ? 'Мероприятие продолжается' : 'Мероприятие завершено' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="no_message">Мероприятий пока нет.</p>
        @endif
    </div>
@endsection
