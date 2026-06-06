@php
    $viewer = $frontLayout['user'] ?? null;
@endphp

<script src="{{ asset('templates/js/photoalbum.js') }}"></script>
<div class="overlay" id="photo_big">
    <div class="overlay-back back_one">Закрыть</div>
    <div class="prev">Предыдущая</div>
    <div class="photo_big_wrap">
        <input type="hidden" id="owner_id">
        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" class="photo_wrap next" alt="">
        <div class="loading-bar"><img border="0" src="{{ asset('templates/images/select2-spinner.gif') }}" width="20" alt=""></div>
        <div class="text">
            <div class="message">
                <div class="text-block" id="name_foto">
                    @if ($viewer)
                        <a href="{{ route('front.profile.show', ['user' => $viewer->id]) }}">{{ $viewer->displayName() }}</a>
                    @endif
                </div>
                <div class="text-block" id="date_foto"><span class="data"></span></div>
                @if ($viewer)
                    <div class="text-block foto_like">
                        <a class="tell" data-type="photo">0</a>
                        <a class="liked" data-type="photo">0</a>
                    </div>
                @endif
                <p class="info_photo"></p>
            </div>
        </div>
        <div class="text" id="mainComments">
            <div id="addCommentContainers" data-type="photo"></div>
        </div>
        <div class="text">
            <form autocomplete="off" id="addCommentForm" class="form-horizontal" method="POST" action="">
                @csrf
                <input type="hidden" name="commentable_type" value="photo">
                <input type="hidden" name="content_id" id="content_id" value="">
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
                            <a id="smilesBtn" class="smile smilesBtn" data-num="0"><img src="{{ asset('templates/images/smile.png') }}" alt=""></a>
                            <a href="#" class="files" data-num="0" data-tooltip="Прикрепить изображение"><img src="{{ asset('templates/images/files.png') }}" alt=""></a>
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
