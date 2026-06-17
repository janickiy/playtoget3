let k = 0;
const mass_photo = [];

function photoCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
}

function resetPhotoModalState() {
    const $modal = $('#photo_big');

    $modal.removeClass('photo-message-viewer');
    $modal.find('#addCommentContainers').html('');
    $modal.find('.info_photo').text('');
    $modal.find('.tell, .liked').removeClass('active').removeAttr('id data-item');
    $modal.find('.file_name').val('');
    $modal.find('textarea[name="comment"]').val('');
    $modal.find('.files_block').html('');
}

function setPhotoModalContext($source) {
    const $modal = $('#photo_big');
    const isDialogPhoto = $source.data('context') === 'dialog'
        || $source.closest('.mess_list').length > 0
        || /\/messages\/user\//.test(window.location.pathname);

    $modal.toggleClass('photo-message-viewer', isDialogPhoto);
}

function isMessagePhotoModal() {
    return $('#photo_big').hasClass('photo-message-viewer');
}

window.registerPhotoItems = function ($scope) {
    $scope.find('.photo_big').each(function () {
        const id = $(this).attr('data-num');

        if (parseInt($.inArray(id, mass_photo)) == -1) {
            mass_photo.push(id);
            k++;
        }
    });
};

$(document).ready(function () {

    $(document).on('click', '.remove_pic', function () {
        const IdPic = $(this).attr('data-item');

        $.confirm({
            'title': 'Confirmation',
            'message': 'Do you really want to delete?',
            'buttons': {
                'Yes': {
                    'class': 'blue',
                    'action': function () {
                        $.ajax({
                            type: 'POST',
                            url: "/ajax/remove_pic?id=" + encodeURIComponent(IdPic),
                            data: {
                                _token: photoCsrfToken(),
                            },
                            cache: false,
                            dataType: "json",
                            success: function (data) {
                                const Result = data.result;
                                if (Result == 'success') {
                                    $('#photo-block-' + IdPic).remove();
                                }
                            }
                        });
                    }
                },
                'No': {
                    'class': 'gray',
                    'action': function () {
                    }
                }
            }
        });
    });
    const obj = {};
    window.registerPhotoItems($(document));


    function getPhotoInfo(id) {
        const $modal = $('#photo_big');

        $.ajax({
            type: 'GET',
            url: '/ajax/get_photoinfo',
            data: 'photo_id=' + encodeURIComponent(id),
            success: function (data) {
                //console.log(data);
                if (data.status === 1) {
                    //console.log(data);
                    $modal.find('#owner_id').val(data.owner_id);
                    $modal.find('#name_foto')
                        .empty()
                        .append($('<a>', {
                            href: '/profile/' + data.owner_id,
                            text: $.trim((data.firstname || '') + ' ' + (data.lastname || '')),
                        }));
                    $modal.find('#date_foto .data').text(data.created);
                    $modal.find('.info_photo').text(data.description || '');
                    if (!isMessagePhotoModal()) {
                        $modal.find('.tell')
                            .text(data.tell)
                            .attr('data-item', id)
                            .attr('id', 'tell-photo-' + id)
                            .attr('data-type', 'photo')
                            .toggleClass('active', Boolean(data.shared_by_user));
                        $modal.find('.liked')
                            .text(data.liked)
                            .attr('data-item', id)
                            .attr('id', 'like-photo-' + id)
                            .attr('data-type', 'photo')
                            .toggleClass('active', Boolean(data.liked_by_user));
                    }
                    $modal.find('.photo_wrap').attr('src', data.photo);
                    if (!isMessagePhotoModal()) {
                        getCommentsPhoto(id);
                    }
                    //window.location.hash="#photo"+id;
                    let url;
                    delParams('photo');
                    const str = delParams('video');

                    const get = parseGetParams();
                    if (get['none'] == 'none')
                        url = '?photo=' + id;
                    else
                        url = '&photo=' + id;
                    if (url != window.location) {
                        window.history.pushState(null, null, str + url);
                    }
                } else {
                    $('.back_one').trigger('click');
                }
            }
        })
    }

    $('.photo_wrap').on('load', function () {
        $('.loading-bar').hide();
        $('.photo_big_wrap').find('.photo_wrap').show();

    });


    $(document).on('click', '.photo_big', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $('.loading-bar').show();
        $('.photo_big_wrap').find('.photo_wrap').hide();
        const id = $(this).attr('data-num');
        resetPhotoModalState();
        setPhotoModalContext($(this));
        $('.photo_big_wrap').find('#content_id').val(id);
        $('body,html').css('overflow', 'hidden');
        $('#photo_big').show();
        $('#photo_big').animate({scrollTop: 0}, 0);
        getPhotoInfo(id);

        return false;
    })

    function showNextPhoto() {
        const $modal = $('#photo_big');

        $modal.find('.loading-bar').show();
        $modal.find('.photo_wrap').hide();
        const id = $modal.find('#content_id').val();
        let index_new = 0;
        const index = parseInt($.inArray(id, mass_photo));
        //console.log(id+'-'+index);
        if (index == k - 1) {
            index_new = 0;
        } else {
            index_new = index + 1;
        }

        $modal.find('#content_id').val(mass_photo[index_new]);
        getPhotoInfo(mass_photo[index_new]);
    }

    function showPrevPhoto() {
        const $modal = $('#photo_big');

        $modal.find('.loading-bar').show();
        $modal.find('.photo_wrap').hide();
        const id = $modal.find('#content_id').val();
        let index_new = 0;
        const index = parseInt($.inArray(id, mass_photo));

        if (index == 0) {
            index_new = k - 1;
        } else {
            index_new = index - 1;
        }

        $modal.find('#content_id').val(mass_photo[index_new]);
        getPhotoInfo(mass_photo[index_new]);
    }

    $(document).on('click', '.next, #next_photo', function (event) {
        event.preventDefault();
        event.stopPropagation();
        showNextPhoto();
    })


    $(document).on('click', '.prev, #prev_photo', function (event) {
        event.preventDefault();
        event.stopPropagation();
        showPrevPhoto();
    })

});

let albumPhotoLoading = false;
$(document).on('scroll', function() {
    const $albumList = $('#album-photo-list');

    if (!$albumList.length || $albumList.attr('data-has-more') !== '1') {
        return;
    }

    if ($(window).scrollTop() + $(window).height() < $(document).height() - 20 || albumPhotoLoading) {
        return;
    }

    albumPhotoLoading = true;
    $albumList.append('<div class="loading-bar"><img border="0" src="/frontend/images/select2-spinner.gif" width="20" alt=""></div>');

    $.ajax({
        type: 'POST',
        url: '/ajax/get_album_photos',
        data: {
            _token: photoCsrfToken(),
            number: $albumList.attr('data-number'),
            offset: $albumList.attr('data-offset'),
            id_album: $albumList.attr('data-album-id'),
        },
        success: function(data) {
            $albumList.find('.loading-bar').remove();

            if (data.status == 1 && data.html != '') {
                $albumList.append(data.html);
                $albumList.attr('data-offset', parseInt($albumList.attr('data-offset'), 10) + parseInt($albumList.attr('data-number'), 10));
                $albumList.attr('data-has-more', data.has_more === false ? '0' : '1');
                window.registerPhotoItems($albumList);
            } else {
                $albumList.attr('data-has-more', '0');
            }
        },
        complete: function() {
            albumPhotoLoading = false;
        }
    });
});

$(document).on("click", ".hide-pop-photo-block", function () {
    $('#popular-photos').hide().fadeIn('2000');
    $('#button-hid').text('Hide');
    $("#button-hid").removeClass("hide-pop-photo-block");
    $('#button-hid').addClass('show-pop-photo-block');

});

$(document).on("click", ".show-pop-photo-block", function () {
    $('#popular-photos').show().fadeOut('2000');
    $('#button-hid').text('Show');
    $("#button-hid").removeClass("show-pop-photo-block");
    $('#button-hid').addClass('hide-pop-photo-block');

});


$(document).on("click", ".photo_big_wrap .reply", function () {
    const IdComment = $(this).attr('data-item');
    const IdParent = $('#content_id').val();
    $('.reply').show();
    $(this).hide();
    $('.my-comment').remove();

    let ReplyForm = '<div id="my-comment-' + IdComment + '" class="my-comment">';
    ReplyForm += '<div class="message-account">';
    ReplyForm += '<img src="' + avatar + '" alt="" class="img-account">';
    ReplyForm += '</div>';
    ReplyForm += '<form autocomplete="off" id="reply-form-' + IdComment + '" data-num = ' + IdComment + ' action="">';
    ReplyForm += '<input type="hidden" name="commentable_type" value="photo">';
    ReplyForm += '<input type="hidden" name="content_id" value="' + IdParent + '">';
    ReplyForm += '<input type="hidden" name="user_id" value="' + user_id + '">';
    ReplyForm += '<input type="hidden" name="parent_id" value="' + IdComment + '">';
    ReplyForm += '<input type="file" class="file_name" name="file_name[]" data-num="' + IdComment + '" multiple/>';
    ReplyForm += '<input id="comment" name="comment" type="text" data-num="' + IdComment + '" placeholder="' + (window.placeholder || '') + '">';
    ReplyForm += '<div class="smile-files">';
    ReplyForm += '<a id="smilesBtn" class="smile smilesBtn" data-num="' + IdComment + '"><img src="./frontend/images/smile.png" alt=""></a>';
    ReplyForm += '<a href="#" class="files" data-num="' + IdComment + '" data-tooltip="Attach image"><img src="./frontend/images/files.png" alt=""></a>';
    ReplyForm += "<div class='smilesChoose add' data-num='" + IdComment + "'></div>";
    ReplyForm += '</div>';
    ReplyForm += "<div class='files_block two' data-num='" + IdComment + "'></div>";
    ReplyForm += '<input type="submit" id="send-reply" value="Reply" class="send" data-item="' + IdComment + '">';
    ReplyForm += '</form>';
    ReplyForm += '<div style="clear:both"></div>';
    ReplyForm += '</div>';

    $(ReplyForm).hide().insertAfter('#message-' + IdComment).slideDown();

});


function getCommentsPhoto(id) {
    $.get('/ajax/get_comments', {
        number: 100,
        offset: 0,
        commentable_type: 'photo',
        id: id
    }, function (data) {
        //console.log(data);
        $('#photo_big').find('#addCommentContainers').html('');
        if (data.html != null) {
            if (data.status == 1) {
                $('#photo_big').find('#addCommentContainers').append(data.html);
                $('.message-text').each(function () {
                    $(this).emotions();
                })
                $('.message-reply-text').each(function () {
                    $(this).emotions();
                })
            }
        }
    }, 'json');
} 
