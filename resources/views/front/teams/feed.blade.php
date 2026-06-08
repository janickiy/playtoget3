@extends('front.layouts.app')

@section('content')
    @php
        $communityView = $communityView ?? [
            'kind' => 'team',
            'route' => 'front.teams',
            'top' => 'front.teams._top',
            'label' => 'Команда',
            'labelLower' => 'команда',
            'entity' => $team,
        ];
        $community = $communityView['entity'] ?? $team;
        $communityKind = $communityView['kind'];
        $canManageCommunity = $communityKind === 'group' ? ($canManageGroup ?? false) : ($canManageTeam ?? false);
    @endphp
    <div class="content-groups friends">
        @include($communityView['top'])

        @if ($permissions['wall'])
            @if ($viewer)
                <div class="message-content">
                    <form autocomplete="off" id="addCommentForm" method="POST" action="" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="commentable_type" value="{{ $communityKind }}">
                        <input type="hidden" name="content_id" value="{{ $community->id }}">
                        <input type="hidden" name="user_id" value="{{ $viewer->id }}">
                        <input type="hidden" name="parent_id" value="0">
                        <input type="file" class="file_name" name="file_name[]" data-num="{{ $community->id }}" multiple>
                        <textarea id="comment" name="comment" data-num="{{ $community->id }}" class="ahref_input" placeholder="Что у Вас интересного?"></textarea>
                        <div class="smile-files">
                            <a id="smilesBtn" class="smile smilesBtn" data-num="{{ $community->id }}">
                                <img src="{{ asset('frontend/images/smile.png') }}" alt="">
                            </a>
                            <a href="#" class="files" data-num="{{ $community->id }}" data-tooltip="Прикрепить изображение">
                                <img src="{{ asset('frontend/images/files.png') }}" alt="">
                            </a>
                            <div class="smilesChoose" data-num="{{ $community->id }}"></div>
                        </div>
                        <input id="submit" type="submit">
                        @if ($canManageCommunity)
                            <div class="col-lg-6 team-signature">
                                <div class="checkbox team_check">
                                    <input id="team_check" type="checkbox" hidden checked name="author_community" value="1">
                                    <label for="team_check"></label>
                                </div>
                                <label class="col-lg-3 control-label label_team_check" for="team_check">подпись</label>
                            </div>
                        @endif
                        <div class="link_attach" data-num="{{ $community->id }}"></div>
                        <div class="files_block" data-num="{{ $community->id }}"></div>
                    </form>
                    <div style="clear:both"></div>
                </div>
                <div id="addCommentContainers" data-type="{{ $communityKind }}"></div>
            @endif

            <div
                id="comment-list"
                data-endpoint="{{ route('front.ajax.handle', ['action' => 'getcomments']) }}"
                data-number="{{ $commentsPageSize }}"
                data-offset="{{ $commentsPageSize }}"
                data-has-more="{{ $hasMoreComments ? 1 : 0 }}"
                data-profile-id="{{ $community->id }}"
                data-commentable-type="{{ $communityKind }}"
            >
                @include('front.profile._comments', ['comments' => $comments, 'viewer' => $viewer])
            </div>
        @else
            <h4 class="blocking">{{ $communityView['label'] }} ограничила доступ к ленте</h4>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .team-signature {
            margin-top: 8px;
            padding-left: 0;
        }

        .team-signature .label_team_check {
            color: #777;
            font-size: 12px;
            padding-left: 8px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.content_id = '{{ $community->id }}';
        window.id_profile = '{{ $community->id }}';
        window.placeholder = 'Ваш комментарий';
        window.profileCommentableType = '{{ $communityKind }}';
        window.profileCanPostAsCommunity = {{ $canManageCommunity ? 'true' : 'false' }};
        window.profileCommentsEndpoint = '{{ route('front.ajax.handle', ['action' => 'getcomments']) }}';
        window.profileCommentsHasMore = {{ $hasMoreComments ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('frontend/js/autoresize.js') }}"></script>
    <script src="{{ asset('frontend/js/profile.js') }}"></script>
@endpush
