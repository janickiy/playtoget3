let count = 0;
const mass_video = [];

function videoCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
}

window.registerVideoItems = function ($scope) {
    $scope.find('.video_prev').each(function () {
        const id = $(this).attr('data-num');

        if (parseInt($.inArray(id, mass_video)) == -1) {
            mass_video.push(id);
            count++;
        }
    });
};

$(document).ready(function () {
    $(document).on('click', '.remove_video', function () {
        const IdVideo = $(this).attr('data-item');

        $.confirm({
            'title': 'Подтверждение',
            'message': 'Вы действительно хотите удалить?',
            'buttons': {
                'Да': {
                    'class': 'blue',
                    'action': function () {
                        $.ajax({
                            type: 'POST',
                            url: '/ajax/remove_video?id=' + encodeURIComponent(IdVideo),
                            data: {
                                _token: videoCsrfToken(),
                            },
                            cache: false,
                            dataType: 'json',
                            success: function (data) {
                                const Result = data.result;

                                if (Result == 'success') {
                                    $('#video-block-' + IdVideo).remove();
                                }
                            }
                        });
                    }
                },
                'Нет': {
                    'class': 'gray',
                    'action': function () {
                    }
                }
            }
        });
    });

    window.registerVideoItems($(document));
});

function getVideoInfo(id) {
    $.ajax({
        type: 'GET',
        url: '/ajax/get_video_info',
        data: {
            video_id: id,
        },
        success: function (data) {
            if (data.status === 1) {
                $('#video_big').find('#owner_id').val(data.owner_id);
                $('#name_video').html('<a href="/profile/' + data.owner_id + '">' + data.firstname + ' ' + data.lastname + '</a>');
                $('#date_video .data').html(data.created);
                $('#video_big').find('.tell').html(data.tell).attr('id', 'tell-video-' + id).attr('data-item', id).attr('data-type', 'video');
                $('#video_big').find('.liked').html(data.liked).attr('id', 'like-video-' + id).attr('data-item', id).attr('data-type', 'video');
                $('.info').html(data.description);
                $('.video_wrap').html(data.video);
                $('.loading-bar').hide();
                $('#video_big').find('.video_wrap').show();
                getVideoComments(id);

                let url;
                delParams('photo');
                const str = delParams('video');
                const get = parseGetParams();

                if (get['none'] == 'none') {
                    url = '?video=' + id;
                } else {
                    url = '&video=' + id;
                }

                if (url != window.location) {
                    window.history.pushState(null, null, str + url);
                }
            } else {
                $('.back_one').trigger('click');
            }
        }
    });
}

$(document).on('click', '.video_prev', function () {
    $('.loading-bar').show();
    $('#video_big').find('.video_wrap').hide();
    const num = $(this).attr('data-num');
    $('#video_big').find('#content_id').val(num);
    $('#video_big').find('input[name="content_id"]').val(num);
    $('body,html').css('overflow', 'hidden');
    $('#video_big').show();
    $('#video_big').animate({scrollTop: 0}, 0);
    getVideoInfo(num);
});

$(document).on('click', '#next_video', function () {
    $('.loading-bar').show();
    $('#video_big').find('.video_wrap').hide();
    const id = $('#video_big').find('#content_id').val();
    let index_new = 0;
    const index = parseInt($.inArray(id, mass_video));

    if (index === count - 1) {
        index_new = 0;
    } else {
        index_new = index + 1;
    }

    $('#video_big').find('#content_id').val(mass_video[index_new]);
    $('#video_big').find('input[name="content_id"]').val(mass_video[index_new]);
    getVideoInfo(mass_video[index_new]);
});

$(document).on('click', '#prev_video', function () {
    $('.loading-bar').show();
    $('#video_big').find('.video_wrap').hide();
    const id = $('#video_big').find('#content_id').val();
    let index_new = 0;
    const index = parseInt($.inArray(id, mass_video));

    if (index === 0) {
        index_new = count - 1;
    } else {
        index_new = index - 1;
    }

    $('#video_big').find('#content_id').val(mass_video[index_new]);
    $('#video_big').find('input[name="content_id"]').val(mass_video[index_new]);
    getVideoInfo(mass_video[index_new]);
});

let albumVideoLoading = false;
$(document).on('scroll', function () {
    const $albumList = $('#album-video-list');

    if (!$albumList.length || $albumList.attr('data-has-more') !== '1') {
        return;
    }

    if ($(window).scrollTop() + $(window).height() < $(document).height() - 20 || albumVideoLoading) {
        return;
    }

    albumVideoLoading = true;
    $albumList.append('<div class="loading-bar"><img border="0" src="/frontend/images/select2-spinner.gif" width="20" alt=""></div>');

    $.ajax({
        type: 'POST',
        url: '/ajax/get_album_videos',
        data: {
            _token: videoCsrfToken(),
            number: $albumList.attr('data-number'),
            offset: $albumList.attr('data-offset'),
            id_album: $albumList.attr('data-album-id'),
        },
        success: function (data) {
            $albumList.find('.loading-bar').remove();

            if (data.status == 1 && data.html != '') {
                $albumList.append(data.html);
                $albumList.attr('data-offset', parseInt($albumList.attr('data-offset'), 10) + parseInt($albumList.attr('data-number'), 10));
                $albumList.attr('data-has-more', data.has_more === false ? '0' : '1');
                window.registerVideoItems($albumList);
            } else {
                $albumList.attr('data-has-more', '0');
            }
        },
        complete: function () {
            albumVideoLoading = false;
        }
    });
});

$(document).on('click', '.hide-pop-video-block', function () {
    $('#popular-videos').hide().fadeIn('2000');
    $('#button-hid').text('Скрыть');
    $('#button-hid').removeClass('hide-pop-video-block');
    $('#button-hid').addClass('show-pop-video-block');
});

$(document).on('click', '.show-pop-video-block', function () {
    $('#popular-videos').show().fadeOut('2000');
    $('#button-hid').text('Показать');
    $('#button-hid').removeClass('show-pop-video-block');
    $('#button-hid').addClass('hide-pop-video-block');
});

$(document).on('click', '#video_big .reply', function () {
    const IdComment = $(this).attr('data-item');
    const IdParent = $('#video_big').find('#content_id').val();
    $('.reply').show();
    $(this).hide();
    $('.my-comment').remove();

    let ReplyForm = '<div id="my-comment-' + IdComment + '" class="my-comment">';
    ReplyForm += '<div class="message-account">';
    ReplyForm += '<img src="' + avatar + '" alt="" class="img-account">';
    ReplyForm += '</div>';
    ReplyForm += '<form autocomplete="off" id="reply-form-' + IdComment + '" data-num="' + IdComment + '" action="">';
    ReplyForm += '<input type="hidden" name="_token" value="' + videoCsrfToken() + '">';
    ReplyForm += '<input type="hidden" name="commentable_type" value="video">';
    ReplyForm += '<input type="hidden" name="content_id" value="' + IdParent + '">';
    ReplyForm += '<input type="hidden" name="user_id" value="' + user_id + '">';
    ReplyForm += '<input type="hidden" name="parent_id" value="' + IdComment + '">';
    ReplyForm += '<input type="file" class="file_name" name="file_name[]" data-num="' + IdComment + '" multiple>';
    ReplyForm += '<input id="comment" name="comment" type="text" data-num="' + IdComment + '" placeholder="' + placeholder + '">';
    ReplyForm += '<div class="smile-files">';
    ReplyForm += '<a id="smilesBtn" class="smile smilesBtn" data-num="' + IdComment + '"><img src="/frontend/images/smile.png" alt=""></a>';
    ReplyForm += '<a href="#" class="files" data-num="' + IdComment + '" data-tooltip="Прикрепить изображение"><img src="/frontend/images/files.png" alt=""></a>';
    ReplyForm += "<div class='smilesChoose add' data-num='" + IdComment + "'></div>";
    ReplyForm += '</div>';
    ReplyForm += "<div class='files_block two' data-num='" + IdComment + "'></div>";
    ReplyForm += '<input type="submit" id="send-reply" class="send" value="Ответить" data-item="' + IdComment + '">';
    ReplyForm += '</form>';
    ReplyForm += '</div>';

    $(ReplyForm).hide().insertAfter('#message-' + IdComment).slideDown();
});

function getVideoComments(id) {
    $.get('/ajax/get_comments', {
        number: 100,
        offset: 0,
        commentable_type: 'video',
        id: id
    }, function (data) {
        $('#video_big').find('#addCommentContainers').html('');

        if (data.html != null && data.status == 1) {
            $('#video_big').find('#addCommentContainers').append(data.html);
            $('.message-text').each(function () {
                $(this).emotions();
            });
            $('.message-reply-text').each(function () {
                $(this).emotions();
            });
        }
    }, 'json');
}
