@extends('front.layouts.app')

@section('content')
    @include('front.profile._top')

    <div class="photo-caption front-section-title">
        <h3>Профиль</h3>
    </div>

    @if ($profileUser->isBlocked())
        <p class="no_message">Пользователь заблокирован.</p>
    @elseif ($profileUser->isDeleted())
        <p class="no_message">Пользователь удален.</p>
    @elseif ($permissions['blocked_by_profile'] ?? false)
        <p class="no_message">Пользователь ограничил доступ к своей странице для вас.</p>
    @elseif (! $permissions['profile'])
        <p class="no_message">Профиль пользователя доступен только друзьям.</p>
    @elseif ($permissions['wall'])
        @if ($viewer)
            <div class="message-content">
                <form autocomplete="off" id="addCommentForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="commentable_type" value="user">
                    <input type="hidden" name="content_id" value="{{ $profileUser->id }}">
                    <input type="hidden" name="user_id" value="{{ $viewer->id }}">
                    <input type="hidden" name="parent_id" value="0">
                    <input type="file" class="file_name" name="file_name[]" data-num="{{ $profileUser->id }}" multiple>
                    <textarea id="comment" name="comment" data-num="{{ $profileUser->id }}" class="ahref_input" placeholder="Что у Вас интересного?"></textarea>
                    <div class="smile-files">
                        <a id="smilesBtn" class="smile smilesBtn" data-num="{{ $profileUser->id }}">
                            <img src="{{ asset('frontend/images/smile.png') }}" alt="">
                        </a>
                        <a href="#" class="files" data-num="{{ $profileUser->id }}" data-tooltip="Прикрепить изображение">
                            <img src="{{ asset('frontend/images/files.png') }}" alt="">
                        </a>
                        <div class="smilesChoose" data-num="{{ $profileUser->id }}"></div>
                    </div>
                    <input id="submit" type="submit">
                    <div class="link_attach" data-num="{{ $profileUser->id }}"></div>
                    <div class="files_block" data-num="{{ $profileUser->id }}"></div>
                </form>
                <div style="clear:both"></div>
            </div>
            <div id="addCommentContainers" data-type="user"></div>
        @endif

        <div
            id="comment-list"
            data-endpoint="{{ route('front.ajax.handle', ['action' => 'get_comments']) }}"
            data-number="{{ $commentsPageSize }}"
            data-offset="{{ $commentsPageSize }}"
            data-has-more="{{ $hasMoreComments ? 1 : 0 }}"
            data-profile-id="{{ $profileUser->id }}"
        >
            @include('front.profile._comments', ['comments' => $comments, 'viewer' => $viewer])
        </div>
    @else
        <p class="no_message">Стена пользователя скрыта настройками приватности.</p>
    @endif
@endsection

@push('scripts')
    <script>
        window.content_id = '{{ $profileUser->id }}';
        window.id_profile = '{{ $profileUser->id }}';
        window.placeholder = 'Ваш комментарий';
        window.profileCommentsEndpoint = '{{ route('front.ajax.handle', ['action' => 'get_comments']) }}';
        window.profileCommentsHasMore = {{ $hasMoreComments ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('frontend/js/autoresize.js') }}"></script>
    <script src="{{ asset('frontend/js/profile.js') }}?v=2026061410"></script>
@endpush
