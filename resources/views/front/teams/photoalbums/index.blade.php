@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Team',
            'labelLower' => 'team',
            'labelGenitive' => 'team',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $communityKind = $communityView['kind'];
        $routeParam = $communityView['routeParam'] ?? 'community';
        $routeParams = [$routeParam => $community->id];
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        @if ($communityAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $communityAccessMessage])
        @elseif ($sectionAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage])
        @elseif (! $permissions['photo'])
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage ?? ($communityView['label'] . ' has restricted access to this section')])
        @else
            @if ($canManage)
                <div class="add-photos-album">
                    <span><i></i><a href="{{ route($communityView['route'] . '.photoalbums.add-photo', $routeParams) }}">Add photo</a></span>
                    <span>or</span>
                    <span><i></i><a href="{{ route($communityView['route'] . '.photoalbums.create', $routeParams) }}">Create photo album</a></span>
                </div>
            @endif

            @if ($popularPhotos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Popular photos</h3>
                    <a id="button-hid" class="button-hid show-pop-photo-block">Hide</a>
                </div>

                <div id="popular-photos">
                    <div class="photo-container pop-photos">
                        @foreach ($popularPhotos as $photo)
                            @include('front.photoalbums._photo-card', ['photo' => $photo, 'canManage' => false])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($albums->isNotEmpty())
                <div class="photo-caption">
                    <h3>Community albums<sup>{{ $albums->count() }}</sup></h3>
                </div>

                <div class="my-albums">
                    @foreach ($albums as $album)
                        @include('front.teams.photoalbums._album-card', ['album' => $album, 'canManage' => $canManage])
                    @endforeach
                </div>
            @endif

            @if ($photos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Community photos</h3>
                </div>

                <div class="photo-container my-photos" id="photo-list">
                    @foreach ($photos as $photo)
                        @include('front.photoalbums._photo-card', ['photo' => $photo, 'canManage' => $canManage])
                    @endforeach
                    @if ($hasMorePhotos)
                        <a class="show-more" id="my-event" onclick="showMorePhotos('{{ $community->id }}', '{{ $communityKind }}')">
                            <i></i><span id="show-more">show more</span>
                        </a>
                    @endif
                </div>
            @elseif ($albums->isEmpty())
                <p class="no_message">No photos yet.</p>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.photoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
