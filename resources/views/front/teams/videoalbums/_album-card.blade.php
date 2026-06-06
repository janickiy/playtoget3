<div class="album">
    <a href="{{ route('front.teams.videoalbums.show', ['community' => $team->id, 'album' => $album['id']]) }}">
        <div class="img-container">
            <img border="0" src="{{ $album['image'] ?: asset('frontend/images/default_group.png') }}" alt="">
        </div>
        <p>{{ $album['name'] }}</p>
    </a>

    @if ($canManage)
        <p>
            <a href="{{ route('front.teams.videoalbum.edit', ['album' => $album['id']]) }}">Редактировать</a>
        </p>
    @endif
</div>
