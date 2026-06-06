@php
    $viewer = $frontLayout['user'] ?? null;
@endphp

<script src="{{ asset('frontend/js/videoalbum.js') }}"></script>
<div class="overlay" id="video_big">
    <div class="overlay-back back_one">Закрыть</div>
    <div class="prev"></div>
    <div class="photo_big_wrap">
        <input type="hidden" id="owner_id">
        <input type="hidden" id="content_id" value="">
        <div class="loading-bar"><img border="0" src="{{ asset('frontend/images/select2-spinner.gif') }}" width="20" alt=""></div>
        <img id="prev_video" src="{{ asset('frontend/images/prev.png') }}" alt="">
        <img id="next_video" src="{{ asset('frontend/images/next.png') }}" alt="">
        <div class="video_wrap"></div>
        <div class="text">
            <div class="message">
                <div class="text-block" id="name_video">
                    @if ($viewer)
                        <a href="{{ route('front.profile.show', ['user' => $viewer->id]) }}">{{ $viewer->displayName() }}</a>
                    @endif
                </div>
                <div class="text-block" id="date_video"><span class="data"></span></div>
                @if ($viewer)
                    <div class="text-block foto_like">
                        <a class="tell" data-type="video">0</a>
                        <a class="liked" data-type="video">0</a>
                    </div>
                @endif
                <p class="info"></p>
            </div>
        </div>
        <div class="text" id="mainComments">
            <div id="addCommentContainers" data-type="video"></div>
        </div>
        <div class="text">
            <form autocomplete="off" id="addCommentForm" class="form-horizontal" method="POST" action="">
                @csrf
                <input type="hidden" name="commentable_type" value="video">
                <input type="hidden" name="content_id" value="">
                <input type="hidden" name="user_id" value="{{ $viewer?->id }}">
                <input type="hidden" name="parent_id" value="0">
                <div class="form-group">
                    @if ($viewer)
                        <label class="col-lg-3 control-label" for="comment">Оставить комментарий</label>
                        <div class="col-lg-7">
                            <input type="file" class="file_name" name="file_name[]" data-num="0" multiple>
                            <textarea class="form-control form-dark" id="comment" rows="4" name="comment" data-num="0"></textarea>
                        </div>
                        <div class="smile-files">
                            <a id="videoSmilesBtn" class="smile smilesBtn" data-num="0"><img src="{{ asset('frontend/images/smile.png') }}" alt=""></a>
                            <a href="#" class="files" data-num="0" data-tooltip="Прикрепить изображение"><img src="{{ asset('frontend/images/files.png') }}" alt=""></a>
                            <div class="smilesChoose" data-num="0"></div>
                        </div>
                        <div class="files_block" data-num="0"></div>
                    @else
                        <label class="col-lg-12 control-label center_text margin0Auto marginTop20" for="comment">Чтобы оставить комментарий авторизуйтесь</label>
                    @endif
                </div>
                @if ($viewer)
                    <div class="control marginLeft">
                        <input class="btn btn-success" type="submit" value="Отправить">
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
