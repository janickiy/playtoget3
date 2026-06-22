@php
    $viewer = $frontLayout['user'] ?? null;
@endphp

<script src="{{ asset('frontend/js/photoalbum.js') }}?v=2026062201"></script>
<div class="overlay photo-viewer-overlay" id="photo_big">
    <div class="overlay-back back_one">Close</div>
    <div class="prev">Previous</div>
    <div class="photo_big_wrap" role="dialog" aria-modal="true" aria-label="Photo preview">
        <input type="hidden" id="owner_id">
        <button type="button" class="photo-modal-close back_one" aria-label="Close"></button>
        <img id="prev_photo" src="{{ asset('frontend/images/prev.png') }}" alt="Previous photo">
        <img id="next_photo" src="{{ asset('frontend/images/next.png') }}" alt="Next photo">
        <div class="photo-modal-image">
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" class="photo_wrap next" alt="">
        </div>
        <div class="loading-bar"><img src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt=""></div>
        <div class="photo-modal-content">
            <div class="text photo-modal-meta">
                <div class="message">
                    <div class="text-block photo-modal-author" id="name_foto">
                        @if ($viewer)
                            <a href="{{ route('front.profile.show', ['user' => $viewer->id]) }}">{{ $viewer->displayName() }}</a>
                        @endif
                    </div>
                    <div class="text-block photo-modal-date" id="date_foto"><span class="data"></span></div>
                    @if ($viewer)
                        <div class="text-block foto_like photo-modal-actions">
                            <a class="tell photo-modal-share" data-type="photo">0</a>
                            <a class="liked photo-modal-like" data-type="photo">0</a>
                        </div>
                    @endif
                    <div class="photo-description-box">
                        <p class="info_photo"></p>
                        <button type="button" class="photo-description-edit">Edit description</button>
                        <form class="photo-description-form" autocomplete="off">
                            @csrf
                            <input type="hidden" name="photo_id" value="">
                            <textarea name="description" maxlength="1000" rows="3" placeholder="Description"></textarea>
                            <div class="photo-description-actions">
                                <button type="submit" class="photo-description-save">Save</button>
                                <button type="button" class="photo-description-cancel">Cancel</button>
                            </div>
                            <div class="photo-description-message"></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="text photo-modal-comments" id="mainComments">
                <div id="addCommentContainers" data-type="photo"></div>
            </div>
            <div class="text photo-modal-comment-form">
                <form autocomplete="off" id="addCommentForm" class="form-horizontal" method="POST" action="">
                    @csrf
                    <input type="hidden" name="commentable_type" value="photo">
                    <input type="hidden" name="content_id" id="content_id" value="">
                    <input type="hidden" name="user_id" value="{{ $viewer?->id }}">
                    <input type="hidden" name="parent_id" value="0">
                    @if ($viewer)
                        <label class="photo-modal-comment-title" for="comment">Leave a comment</label>
                        <div class="form-group photo-modal-form-field">
                            <div class="photo-modal-textarea-wrap">
                                <input type="file" class="file_name" name="file_name[]" data-num="0" multiple>
                                <textarea class="form-control form-dark" id="comment" rows="4" name="comment" data-num="0"></textarea>
                                <div class="smile-files">
                                    <a id="smilesBtn" class="smile smilesBtn" data-num="0"><img src="{{ asset('frontend/images/smile.png') }}" alt=""></a>
                                    <a href="#" class="files" data-num="0" data-tooltip="Attach image"><img src="{{ asset('frontend/images/files.png') }}" alt=""></a>
                                    <div class="smilesChoose" data-num="0"></div>
                                </div>
                                <div class="files_block" data-num="0"></div>
                            </div>
                        </div>
                        <div class="control photo-modal-submit">
                            <input class="btn btn-success" type="submit" value="Send">
                        </div>
                    @else
                        <div class="photo-modal-login-note">Log in to leave a comment</div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
