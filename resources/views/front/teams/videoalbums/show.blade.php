@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @include('front.teams._top')

        @if (! $permissions['video'])
            <h4 class="blocking">Команда ограничила доступ к этому разделу</h4>
        @else
            <h2>{{ $videoalbum->name }}</h2>
            <p>
                <a href="{{ route('front.teams.videoalbums', ['community' => $team->id]) }}">
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
