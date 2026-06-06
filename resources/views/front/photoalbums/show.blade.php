@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    @if (! $permissions['photo'])
        <h4 class="blocking">Пользователь ограничил доступ к этому разделу</h4>
    @else
        <h2>{{ $photoalbum->name }}</h2>
        <p>
            <a href="{{ $canManage ? route('front.photoalbums.index') : route('front.photoalbums.user', ['user' => $profileUser->id]) }}">
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
            <p class="no_message">Фотографий пока нет.</p>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        window.photoAjaxBase = '{{ url('/ajax') }}';
    </script>
@endpush
