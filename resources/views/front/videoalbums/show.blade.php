@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    <div class="video-albums-page">
        @if (! $permissions['video'])
            <h4 class="blocking">The user has restricted access to this section</h4>
        @else
            <h2>{{ $videoAlbum->name }}</h2>
            <p>
                <a href="{{ $canManage ? route('front.videoalbums.index') : route('front.videoalbums.user', ['user' => $profileUser->id]) }}">
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
                    <p class="no_message">No videos yet.</p>
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
