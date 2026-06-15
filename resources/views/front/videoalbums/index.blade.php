@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    <div class="photo-caption front-section-title">
        <h3>Видео</h3>
    </div>

    <div class="video-albums-page">
        @if (! $permissions['video'])
            <h4 class="blocking">Пользователь ограничил доступ к этому разделу</h4>
        @else
            @if ($canManage)
                <div class="add-photos-album">
                    <span><i class="videoicon"></i><a href="{{ route('front.videoalbums.add-video') }}">Добавить новую видеозапись</a></span>
                    <span>или</span>
                    <span><i></i><a href="{{ route('front.videoalbums.create') }}">Создать новый альбом</a></span>
                </div>
            @endif

            @if ($showPopular && $popularVideos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Популярные видео</h3>
                    <a id="button-hid" class="button-hid show-pop-video-block">Скрыть</a>
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
                        {{ $canManage ? 'Мои альбомы' : 'Альбомы пользователя' }}
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
                    <h3>{{ $canManage ? 'Мои видео' : 'Видео пользователя' }}</h3>
                </div>

                <div class="photo-container video-container vid-no-border my-videos" id="video-list">
                    @foreach ($videos as $video)
                        @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => $canManage])
                    @endforeach
                    @if ($hasMoreVideos)
                        <a class="show-more" id="my-event" onclick="showMoreVideos('{{ $profileUser->id }}', 'user')">
                            <i></i><span id="show-more">показать ещё</span>
                        </a>
                    @endif
                </div>
            @elseif ($albums->isEmpty())
                <p class="no_message">Видео пока нет.</p>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.videoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
