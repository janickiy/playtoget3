<div class="event-item">
    <a href="{{ route($routePrefix . '.index', ['sportBlock' => $item['id']]) }}" class="img">
        <img src="{{ $item['avatar'] }}" alt="">
    </a>
    <div class="teg">
        <p><a href="{{ route($routePrefix . '.index', ['sportBlock' => $item['id']]) }}">{{ $item['name'] }}</a></p>
        @if ($item['place'])
            <p>{{ $item['place'] }}</p>
        @endif
        @if ($item['about'])
            <p>{!! nl2br(e($item['about'])) !!}</p>
        @endif
        @if ($item['owner_id'] && $viewer && (int) $item['owner_id'] === (int) $viewer->id)
            <a href="{{ route($routePrefix . '.edit', ['sportBlock' => $item['id']]) }}">{{ $editLabel ?? 'Edit' }}</a>
        @endif
    </div>
</div>
