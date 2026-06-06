@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @include('front.teams._top')

        @if (! $permissions['video'])
            <h4 class="blocking">Команда ограничила доступ к этому разделу</h4>
        @else
            @if ($canManage)
                <div class="add-photos-album">
                    <span><i class="videoicon"></i><a href="{{ route('front.teams.videoalbums.add-video', ['community' => $team->id]) }}">Добавить видео</a></span>
                    <span>или</span>
                    <span><i></i><a href="{{ route('front.teams.videoalbums.create', ['community' => $team->id]) }}">Создать видеоальбом</a></span>
                </div>
            @endif

            @if ($popularVideos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Популярные видео</h3>
                    <a id="button-hid" class="button-hid show-pop-video-block">Скрыть</a>
                </div>

                <div id="popular-videos">
                    <div class="photo-container video-container">
                        @foreach ($popularVideos as $video)
                            @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => false])
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($albums->isNotEmpty())
                <div class="photo-caption">
                    <h3>Видеоальбомы сообщества<sup>{{ $albums->count() }}</sup></h3>
                </div>

                <div class="my-albums">
                    @foreach ($albums as $album)
                        @include('front.teams.videoalbums._album-card', ['album' => $album, 'canManage' => $canManage])
                    @endforeach
                </div>
            @endif

            @if ($videos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Видео сообщества</h3>
                </div>

                <div class="photo-container video-container vid-no-border" id="video-list">
                    @foreach ($videos as $video)
                        @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => $canManage])
                    @endforeach
                    @if ($hasMoreVideos)
                        <a class="show-more" id="my-event" onclick="showMoreVideos('{{ $team->id }}', 'team')">
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
