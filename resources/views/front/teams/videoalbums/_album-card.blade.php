<div class="album">
    @php
        $communityView = $communityView ?? ['route' => 'front.teams', 'entity' => $team];
        $community = $communityView['entity'] ?? $team;
    @endphp
    <a href="{{ route($communityView['route'] . '.videoalbums.show', ['community' => $community->id, 'album' => $album['id']]) }}">
        <div class="img-container">
            <img border="0" src="{{ $album['image'] ?: asset('frontend/images/default_group.png') }}" alt="">
        </div>
        <p>{{ $album['name'] }}</p>
    </a>

    @if ($canManage)
        <p>
            <a href="{{ route($communityView['route'] . '.videoalbum.edit', ['album' => $album['id']]) }}">Редактировать</a>
        </p>
    @endif
</div>
