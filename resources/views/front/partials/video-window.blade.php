@php
    $viewer = $frontLayout['user'] ?? null;
@endphp

<script src="{{ asset('frontend/js/videoalbum.js') }}?v=2026061501"></script>
<div class="overlay video-viewer-overlay" id="video_big">
    <div class="overlay-back back_one">Close</div>
    <div class="photo_big_wrap">
        <input type="hidden" id="owner_id">
        <input type="hidden" id="content_id" value="">
        <img id="prev_video" src="{{ asset('frontend/images/prev.png') }}" alt="">
        <img id="next_video" src="{{ asset('frontend/images/next.png') }}" alt="">
        <div class="video-modal-media">
            <div class="loading-bar"><img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt=""></div>
            <div class="video-modal-owner">
                <img id="owner_avatar_video" src="{{ $frontLayout['avatar'] }}" alt="">
                <div id="name_video">
                    @if ($viewer)
                        <a href="{{ route('front.profile.show', ['user' => $viewer->id]) }}">{{ $viewer->displayName() }}</a>
                    @endif
                </div>
            </div>
            <div class="video_wrap"></div>
        </div>
        <div class="video-modal-content">
        <div class="text video-modal-meta">
            <div class="message">
                <div class="text-block video-modal-date" id="date_video"><span class="data"></span></div>
                @if ($viewer)
                    <div class="text-block foto_like video-modal-actions">
                        <a class="tell video-modal-share" data-type="video">0</a>
                        <a class="liked video-modal-like" data-type="video">0</a>
                    </div>
                @endif
                <p class="info"></p>
            </div>
        </div>
        <div class="text video-modal-comments" id="mainComments">
            <div id="addCommentContainers" data-type="video"></div>
        </div>
        <div class="text video-modal-comment-form">
            <form autocomplete="off" id="addCommentForm" class="form-horizontal" method="POST" action="">
                @csrf
                <input type="hidden" name="commentable_type" value="video">
                <input type="hidden" name="content_id" value="">
                <input type="hidden" name="user_id" value="{{ $viewer?->id }}">
                <input type="hidden" name="parent_id" value="0">
                @if ($viewer)
                    <label class="video-modal-comment-title" for="video_comment">Leave a comment</label>
                    <div class="form-group video-modal-form-field">
                        <div class="video-modal-textarea-wrap">
                            <input type="file" class="file_name" name="file_name[]" data-num="0" multiple>
                            <textarea class="form-control form-dark" id="video_comment" rows="4" name="comment" data-num="0"></textarea>
                            <div class="smile-files">
                                <a id="videoSmilesBtn" class="smile smilesBtn" data-num="0"><img src="{{ asset('frontend/images/smile.png') }}" alt=""></a>
                                <a href="#" class="files" data-num="0" data-tooltip="Attach image"><img src="{{ asset('frontend/images/files.png') }}" alt=""></a>
                                <div class="smilesChoose" data-num="0"></div>
                            </div>
                            <div class="files_block" data-num="0"></div>
                        </div>
                    </div>
                    <div class="control video-modal-submit">
                        <input class="btn btn-success" type="submit" value="Send">
                    </div>
                @else
                    <div class="video-modal-login-note">Log in to leave a comment</div>
                @endif
            </form>
        </div>
        </div>
    </div>
</div>
