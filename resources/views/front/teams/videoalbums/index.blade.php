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
        $communityKind = $communityView['kind'];
        $routeParam = $communityView['routeParam'] ?? 'community';
        $routeParams = [$routeParam => $community->id];
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
            @if ($canManage)
                <div class="add-photos-album">
                    <span><i class="videoicon"></i><a href="{{ route($communityView['route'] . '.videoalbums.add-video', $routeParams) }}">Добавить новую видеозапись</a></span>
                    <span>или</span>
                    <span><i></i><a href="{{ route($communityView['route'] . '.videoalbums.create', $routeParams) }}">Создать новый альбом</a></span>
                </div>
            @endif

            @if ($popularVideos->isNotEmpty())
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
                    <h3>Видеоальбомы сообщества<sup>{{ $albums->count() }}</sup></h3>
                </div>

                <div class="my-albums video-album-list">
                    @foreach ($albums as $album)
                        @include('front.teams.videoalbums._album-card', ['album' => $album, 'canManage' => $canManage])
                    @endforeach
                </div>
            @endif

            @if ($videos->isNotEmpty())
                <div class="photo-caption">
                    <h3>Видео сообщества</h3>
                </div>

                <div class="photo-container video-container vid-no-border my-videos" id="video-list">
                    @foreach ($videos as $video)
                        @include('front.videoalbums._video-card', ['video' => $video, 'canManage' => $canManage])
                    @endforeach
                    @if ($hasMoreVideos)
                        <a class="show-more" id="my-event" onclick="showMoreVideos('{{ $community->id }}', '{{ $communityKind }}')">
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
