<div class="album">
    @php
        $communityView = $communityView ?? ['route' => 'front.teams', 'entity' => $team];
        $community = $communityView['entity'] ?? $team;
        $routeParam = $communityView['routeParam'] ?? 'community';
        $routeParams = [$routeParam => $community->id, 'album' => $album['id']];
        $editRoute = $communityView['route'] . '.photoalbum.edit.with-community';
        $editParams = $routeParams;
        $destroyRoute = $communityView['route'] . '.photoalbum.destroy.with-community';
        $destroyParams = $routeParams;

        if (! \Illuminate\Support\Facades\Route::has($editRoute)) {
            $editRoute = $communityView['route'] . '.photoalbum.edit';
            $editParams = $routeParam === 'community' ? ['album' => $album['id']] : $routeParams;
        }

        if (! \Illuminate\Support\Facades\Route::has($destroyRoute)) {
            $destroyRoute = $communityView['route'] . '.photoalbum.destroy';
            $destroyParams = $routeParam === 'community' ? ['album' => $album['id']] : $routeParams;
        }
    @endphp
    <a href="{{ route($communityView['route'] . '.photoalbums.show', $routeParams) }}">
        <div class="img-container">
            <img src="{{ $album['image'] ?: asset('frontend/images/default_group.png') }}" alt="">
        </div>
        <p>{{ $album['name'] }}</p>
    </a>

    @if ($canManage)
        <p>
            <a href="{{ route($editRoute, $editParams) }}">Edit</a>
            <form class="album-delete-form" method="POST" action="{{ route($destroyRoute, $destroyParams) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="remove_album">Delete</button>
            </form>
        </p>
    @endif
</div>
