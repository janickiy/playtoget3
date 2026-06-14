<div class="album">
    <a href="{{ route('front.photoalbums.show', ['album' => $album['id']]) }}">
        <div class="img-container">
            <img src="{{ $album['image'] ?: asset('frontend/images/default_group.png') }}" alt="">
        </div>
        <p>{{ $album['name'] }}</p>
    </a>

    @if ($canManage)
        <p>
            <a href="{{ route('front.photoalbums.edit', ['album' => $album['id']]) }}">Редактировать</a>
            <form class="album-delete-form" method="POST" action="{{ route('front.photoalbums.destroy', ['album' => $album['id']]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="remove_album">Удалить</button>
            </form>
        </p>
    @endif
</div>
