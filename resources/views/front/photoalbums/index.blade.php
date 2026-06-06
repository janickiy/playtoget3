@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    @if (! $permissions['photo'])
        <h4 class="blocking">Пользователь ограничил доступ к этому разделу</h4>
    @else
        @if ($canManage)
            <div class="add-photos-album">
                <span><i></i><a href="{{ route('front.photoalbums.add-photo') }}">Добавить фото</a></span>
                <span>или</span>
                <span><i></i><a href="{{ route('front.photoalbums.create') }}">Создать фотоальбом</a></span>
            </div>
        @endif

        @if ($showPopular && $popularPhotos->isNotEmpty())
            <div class="photo-caption">
                <h3>Популярные фото</h3>
                <a id="button-hid" class="button-hid show-pop-photo-block">Скрыть</a>
            </div>

            <div id="popular-photos">
                <div class="photo-container pop-photos">
                    @foreach ($popularPhotos as $photo)
                        @include('front.photoalbums._photo-card', ['photo' => $photo, 'canManage' => false])
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

            <div class="my-albums">
                @foreach ($albums as $album)
                    @include('front.photoalbums._album-card', ['album' => $album, 'canManage' => $canManage])
                @endforeach
            </div>
        @endif

        @if ($photos->isNotEmpty())
            <div class="photo-caption">
                <h3>{{ $canManage ? 'Мои фото' : 'Фото пользователя' }}</h3>
            </div>

            <div class="photo-container my-photos" id="photo-list">
                @foreach ($photos as $photo)
                    @include('front.photoalbums._photo-card', ['photo' => $photo, 'canManage' => $canManage])
                @endforeach
                @if ($hasMorePhotos)
                    <a class="show-more" id="my-event" onclick="showMorePhotos('{{ $profileUser->id }}', 'user')">
                        <i></i><span id="show-more">показать ещё</span>
                    </a>
                @endif
            </div>
        @elseif ($albums->isEmpty())
            <p class="no_message">Фотографий пока нет.</p>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        window.photoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
