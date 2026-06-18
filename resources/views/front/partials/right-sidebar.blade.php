@php use App\Helpers\FrontAssets; @endphp
<script src="{{ asset('frontend/js/jquery.sticky-kit.min.js') }}"></script>
<div class="right-sitebar">
    <ul>
        <li>
            <a href="{{ route('front.playgrounds.index') }}">
                Playgrounds
                @if ($frontLayout['playgroundsCount'] > 0)
                    <span>{{ $frontLayout['playgroundsCount'] }}</span>
                @endif
            </a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @forelse ($frontLayout['playgrounds'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.playgrounds.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)
                                        <p>{{ $item->place }}</p>
                                    @endif
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @empty
                            <div class="right-sidebar-empty">Nothing yet</div>
                        @endforelse
                    </div>
                </li>
            </ul>
        </li>
        <li>
            <a href="{{ route('front.shops.index') }}">
                Shops
                @if ($frontLayout['shopsCount'] > 0)
                    <span>{{ $frontLayout['shopsCount'] }}</span>
                @endif
            </a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @forelse ($frontLayout['shops'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.shops.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)
                                        <p>{{ $item->place }}</p>
                                    @endif
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        @empty
                            <div class="right-sidebar-empty">Nothing yet</div>
                        @endforelse
                    </div>
                </li>
            </ul>
        </li>
        <li>
            <a href="{{ route('front.fitness.index') }}">
                Fitness
                @if ($frontLayout['fitnessCount'] > 0)
                    <span>{{ $frontLayout['fitnessCount'] }}</span>
                @endif
            </a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @forelse ($frontLayout['fitness'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.fitness.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)
                                        <p>{{ $item->place }}</p>
                                    @endif
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @empty
                            <div class="right-sidebar-empty">Nothing yet</div>
                        @endforelse
                    </div>
                </li>
            </ul>
        </li>
    </ul>
</div>
<script>
    $(document).ready(function () {
        $('.right-sitebar').stick_in_parent({offset_top: 140});
    });
</script>
