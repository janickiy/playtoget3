@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Команда',
            'labelGenitive' => 'команды',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $routeParam = $communityView['routeParam'] ?? 'community';
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        @if ($communityAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $communityAccessMessage])
        @elseif ($sectionAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage])
        @elseif (! $permissions['photo'])
            @include('front.communities._closed-message', ['message' => $sectionAccessMessage ?? ($communityView['label'] . ' ограничила доступ к этому разделу')])
        @else
            <div class="photo-caption album-show-title">
                <h3>{{ $photoalbum->name }}</h3>
            </div>
            <p class="album-show-back">
                <a href="{{ route($communityView['route'] . '.photoalbums', [$routeParam => $community->id]) }}">
                    Все фото
                </a>
            </p>

            @if ($photos->isNotEmpty())
                <div
                    class="photo-container pop-photos"
                    id="album-photo-list"
                    data-album-id="{{ $photoalbum->id }}"
                    data-number="{{ $photosPageSize }}"
                    data-offset="{{ $photosPageSize }}"
                    data-has-more="{{ $hasMorePhotos ? 1 : 0 }}"
                >
                    @foreach ($photos as $photo)
                        @include('front.photoalbums._photo-card', ['photo' => $photo, 'canManage' => $canManage])
                    @endforeach
                </div>
            @else
                <div class="photo-caption">
                    <h5 class="center_text">У {{ $communityView['labelGenitive'] ?? 'команды' }} пока нет фотографий</h5>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        window.photoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
