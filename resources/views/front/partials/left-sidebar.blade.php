@php use App\Helpers\FrontAssets; @endphp
<div class="left-sitebar">
    <ul>
        <li>
            <img src="{{ asset('templates/images/left-sitebar.png') }}" alt="">
            <a href="#">Рекомендуем</a>
        </li>
        <li class="ads">
            <a href="{{ route('front.events.index') }}">Мероприятия<span>{{ $frontLayout['eventCount'] }}</span></a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @foreach ($frontLayout['events'] as $event)
                            <div class="block_right">
                                <div class="wrap_img_right">
                                    <img src="{{ FrontAssets::eventCover($event) }}" alt="">
                                </div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.events.show', ['event' => $event->id]) }}"><h5>{{ $event->name }}</h5></a>
                                    <p>{{ $event->date_from?->format('d.m.Y') }}</p>
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @endforeach
                    </div>
                </li>
            </ul>
        </li>
        <li class="ads">
            <a href="#">Реклама</a>
        </li>
    </ul>
</div>
