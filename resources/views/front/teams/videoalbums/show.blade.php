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
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        @if (! $permissions['video'])
            <h4 class="blocking">{{ $communityView['label'] }} ограничила доступ к этому разделу</h4>
        @else
            <h2>{{ $videoalbum->name }}</h2>
            <p>
                <a href="{{ route($communityView['route'] . '.videoalbums', ['community' => $community->id]) }}">
                    Все видео
                </a>
            </p>

            <div
                class="photo-container video-container vid-no-border"
                id="album-video-list"
                data-album-id="{{ $videoalbum->id }}"
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
