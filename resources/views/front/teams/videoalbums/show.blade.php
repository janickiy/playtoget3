@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Team',
            'labelGenitive' => 'team',
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
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage ?? ($communityView['label'] . ' has restricted access to this section')])
        @else
            <div class="photo-caption album-show-title">
                <h3>{{ $videoAlbum->name }}</h3>
            </div>
            <p class="album-show-back">
                <a href="{{ route($communityView['route'] . '.videoalbums', [$routeParam => $community->id]) }}">
                    All videos
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
                    <div class="photo-caption album-show-empty">
                        <h5 class="center_text">{{ ucfirst($communityView['label'] ?? 'Team') }} has no videos yet</h5>
                    </div>
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
