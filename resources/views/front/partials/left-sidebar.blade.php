@php use App\Helpers\FrontAssets; @endphp
<div class="left-sitebar">
    <ul>
        <li>
            <img src="{{ asset('frontend/images/left-sitebar.png') }}" alt="">
            <a href="#">recommends</a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @forelse ($frontLayout['recommended'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right">
                                    <img src="{{ $item['image'] }}" alt="">
                                </div>
                                <div class="text-right-block">
                                    <a href="{{ $item['url'] }}"><h5>{{ $item['title'] }}</h5></a>
                                    @if ($item['subtitle'])
                                        <p>{{ $item['subtitle'] }}</p>
                                    @endif
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @empty
                            <h5 class="marginNone">There are no recommendations yet.</h5>
                        @endforelse
                    </div>
                </li>
            </ul>
        </li>
        <li class="ads">
            <a href="{{ route('front.events.index') }}">Events<span>{{ $frontLayout['eventCount'] }}</span></a>
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
            <a href="{{ route('front.announcements.index') }}">
                Announcements
                @if ($frontLayout['announcementsCount'] > 0)
                    <span>{{ $frontLayout['announcementsCount'] }}</span>
                @endif
            </a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @forelse ($frontLayout['announcements'] as $announcement)
                            <div class="block_right">
                                <div class="wrap_img_right">
                                    <img src="{{ asset('frontend/images/noimage.png') }}" alt="">
                                </div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.announcements.show', ['slug' => $announcement->slug]) }}"><h5>{{ $announcement->title }}</h5></a>
                                    <p>{{ $announcement->created_at?->format('d.m.Y') }}</p>
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @empty
                            <h5>There are no ads yet</h5>
                        @endforelse
                    </div>
                </li>
            </ul>
        </li>
    </ul>
</div>
