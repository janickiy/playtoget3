<div class="album">
    @php
        $communityView = $communityView ?? ['route' => 'front.teams', 'entity' => $team];
        $community = $communityView['entity'] ?? $team;
    @endphp
    <a href="{{ route($communityView['route'] . '.photoalbums.show', ['community' => $community->id, 'album' => $album['id']]) }}">
        <div class="img-container">
            <img border="0" src="{{ $album['image'] ?: asset('frontend/images/default_group.png') }}" alt="">
        </div>
        <p>{{ $album['name'] }}</p>
    </a>

    @if ($canManage)
        <p>
            <a href="{{ route($communityView['route'] . '.photoalbum.edit', ['album' => $album['id']]) }}">Редактировать</a>
            <form class="album-delete-form" method="POST" action="{{ route($communityView['route'] . '.photoalbum.destroy', ['album' => $album['id']]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="remove_album">Удалить</button>
            </form>
        </p>
    @endif
</div>
