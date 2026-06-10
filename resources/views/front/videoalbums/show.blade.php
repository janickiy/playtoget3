@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    @if (! $permissions['video'])
        <h4 class="blocking">Пользователь ограничил доступ к этому разделу</h4>
    @else
        <h2>{{ $videoAlbum->name }}</h2>
        <p>
            <a href="{{ $canManage ? route('front.videoalbums.index') : route('front.videoalbums.user', ['user' => $profileUser->id]) }}">
                Все видео
            </a>
        </p>

        <div
            class="photo-container video-container vid-no-border"
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
@endsection

@push('scripts')
    <script>
        window.videoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
