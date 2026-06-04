@php use App\Helpers\FrontAssets; @endphp
<script src="{{ asset('templates/js/jquery.sticky-kit.min.js') }}"></script>
<div class="right-sitebar">
    <ul>
        <li>
            <a href="{{ route('front.playgrounds.index') }}">Площадки @if ($frontLayout['playgroundsCount'] > 0)<span>{{ $frontLayout['playgroundsCount'] }}</span>@endif</a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @foreach ($frontLayout['playgrounds'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.playgrounds.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)<p>{{ $item->place }}</p>@endif
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @endforeach
                    </div>
                </li>
            </ul>
        </li>
        <li>
            <a href="{{ route('front.shops.index') }}">Магазины @if ($frontLayout['shopsCount'] > 0)<span>{{ $frontLayout['shopsCount'] }}</span>@endif</a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @foreach ($frontLayout['shops'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.shops.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)<p>{{ $item->place }}</p>@endif
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        @endforeach
                    </div>
                </li>
            </ul>
        </li>
        <li>
            <a href="{{ route('front.fitness.index') }}">Фитнес @if ($frontLayout['fitnessCount'] > 0)<span>{{ $frontLayout['fitnessCount'] }}</span>@endif</a>
            <ul class="sub-menu">
                <li>
                    <div class="sub-content">
                        @foreach ($frontLayout['fitness'] as $item)
                            <div class="block_right">
                                <div class="wrap_img_right"><img src="{{ FrontAssets::sportBlockAvatar($item) }}" alt=""></div>
                                <div class="text-right-block">
                                    <a href="{{ route('front.fitness.index', ['sportBlock' => $item->id]) }}"><h5>{{ $item->name }}</h5></a>
                                    @if ($item->place)<p>{{ $item->place }}</p>@endif
                                </div>
                                <div class="crearfix"></div>
                            </div>
                        @endforeach
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
