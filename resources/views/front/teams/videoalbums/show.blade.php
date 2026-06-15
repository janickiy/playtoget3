@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Команда',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $routeParam = $communityView['routeParam'] ?? 'community';
    @endphp
    <div class="content-groups friends video-albums-page">
        @include($communityView['top'])

        @if ($communityAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $communityAccessMessage])
        @elseif ($sectionAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage])
        @elseif (! $permissions['video'])
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage ?? ($communityView['label'] . ' ограничила доступ к этому разделу')])
        @else
            <h2>{{ $videoAlbum->name }}</h2>
            <p>
                <a href="{{ route($communityView['route'] . '.videoalbums', [$routeParam => $community->id]) }}">
                    Все видео
                </a>
            </p>

            <div
                class="photo-container video-container vid-no-border album-videos"
                id="album-video-list"
                data-album-id="{{ $videoAlbum->id }}"
                data-number="{{ $videosPageSize }}"
                data-offset="{{ $videosPageSize }}"
                data-has-more="{{ $hasMoreVideos ? 1 : 0 }}"
            >
                @forelse ($videos as $video)
                    @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => $canManage])
                @empty
                    <p class="no_message">Видео пока нет.</p>
                @endforelse
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.videoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
