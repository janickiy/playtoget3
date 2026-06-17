@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    <div class="photo-caption front-section-title">
        <h3>Video</h3>
    </div>

    <div class="video-albums-page">
        @if (! $permissions['video'])
            <h4 class="blocking">The user has restricted access to this section</h4>
        @else
            @if ($canManage)
                <div class="add-photos-album">
                    <span><i class="videoicon"></i><a href="{{ route('front.videoalbums.add-video') }}">Add new video</a></span>
                    <span>or</span>
                    <span><i></i><a href="{{ route('front.videoalbums.create') }}">Create new album</a></span>
                </div>
            @endif

            @if ($showPopular && $popularVideos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Popular videos</h3>
                    <a id="button-hid" class="button-hid show-pop-video-block">Hide</a>
                </div>

                <div id="popular-videos">
                    <div class="photo-container video-container pop-videos">
                        @foreach ($popularVideos as $video)
                            @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => false])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($albums->isNotEmpty())
                <div class="photo-caption">
                    <h3>
                        {{ $canManage ? 'My albums' : 'User albums' }}
                        <sup>{{ $albums->count() }}</sup>
                    </h3>
                </div>

                <div class="my-albums video-album-list">
                    @foreach ($albums as $album)
                        @include('front.videoalbums._album-card', ['album' => $album, 'canManage' => $canManage])
                    @endforeach
                </div>
            @endif

            @if ($videos->isNotEmpty())
                <div class="photo-caption">
                    <h3>{{ $canManage ? 'My videos' : 'User videos' }}</h3>
                </div>

                <div class="photo-container video-container vid-no-border my-videos" id="video-list">
                    @foreach ($videos as $video)
                        @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => $canManage])
                    @endforeach
                    @if ($hasMoreVideos)
                        <a class="show-more" id="my-event" onclick="showMoreVideos('{{ $profileUser->id }}', 'user')">
                            <i></i><span id="show-more">show more</span>
                        </a>
                    @endif
                </div>
            @elseif ($albums->isEmpty())
                <p class="no_message">No videos yet.</p>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.videoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
