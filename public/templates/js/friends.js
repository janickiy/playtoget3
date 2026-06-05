(function ($) {
    const $root = $('.friends.content-groups');

    if (!$root.length) {
        return;
    }

    const ajaxBase = String($root.data('ajax-base') || '/ajax').replace(/\/$/, '');
    const csrfToken = String($root.data('csrf') || '');
    const profileBase = String($root.data('profile-base') || '/profile').replace(/\/$/, '');
    const messageBase = String($root.data('message-base') || profileBase).replace(/\/$/, '');
    const iconOk = String($root.data('icon-ok') || '/templates/images/icon-ok.png');
    const iconRemove = String($root.data('icon-remove') || '/templates/images/icon-krest.png');
    const moreFriend = {
        number: 10,
        offset: 10,
        loading: false,
    };

    function ajaxUrl(action) {
        return ajaxBase + '/' + action;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function profileUrl(userId) {
        return profileBase + '/' + encodeURIComponent(userId);
    }

    function messageUrl(userId) {
        return messageBase + '?q=messages&sel=' + encodeURIComponent(userId);
    }

    function renderFriend(user, action, showMessage) {
        const statusUser = user.status_user === 'online' ? ' online' : '';
        let html = '';

        html += '<div class="col-xs-6 possible-friend-cart" data-num="' + escapeHtml(user.user_id) + '">';
        html += '<a class="possible-avatar" href="' + profileUrl(user.user_id) + '">';
        html += '<img src="' + escapeHtml(user.avatar) + '" alt=""></a>';
        html += '<a href="' + profileUrl(user.user_id) + '"><h5><strong>';
        html += escapeHtml(user.firstname) + '<span class="status_user' + statusUser + '" data-num="' + escapeHtml(user.user_id) + '"></span><br>';
        html += escapeHtml(user.lastname) + '</strong></h5></a>';

        html += '<p>' + escapeHtml(user.city) + '</p>';

        if (showMessage !== false) {
            html += '<a href="' + messageUrl(user.user_id) + '" data-tooltip="Написать сообщение"><b></b></a>';
        }

        if (action === 'add') {
            html += '<div class="control">';
            html += '<span><a onclick="add_as_friend(' + Number(user.user_id) + ');" data-tooltip="Добавить в друзья"><img src="' + iconOk + '" alt=""></a></span>';
            html += '<span><img src="' + iconRemove + '" alt="" class="js-hide-possible-friend" data-num="' + Number(user.user_id) + '" data-tooltip="Больше не показывать"></span>';
            html += '</div>';
        } else if (action === 'remove') {
            html += '<div class="control"><span></span>';
            html += '<span><a onclick="remove_friend(' + Number(user.user_id) + ');" data-tooltip="Удалить из друзей"><img src="' + iconRemove + '" alt=""></a></span>';
            html += '</div>';
        } else if (action === 'accept') {
            html += '<div class="control">';
            html += '<span><a onclick="accept_friendship(' + Number(user.user_id) + ');" data-tooltip="Принять заявку"><img src="' + iconOk + '" alt=""></a></span>';
            html += '<span></span></div>';
        }

        html += '</div>';

        return html;
    }

    function loadPossibleFriends(replaceList) {
        $.ajax({
            url: ajaxUrl('getpossiblefriends'),
            dataType: 'json',
            data: {
                number: 6,
            },
            success: function (data) {
                const items = Array.isArray(data.item) ? data.item : [];

                if (!items.length) {
                    $('#show-possible_friends').hide();

                    if (replaceList) {
                        $('#possible-friend').html('');
                    }

                    return;
                }

                const html = items.map(function (item) {
                    return renderFriend(item, 'add', false);
                }).join('');

                if (replaceList) {
                    $('#possible-friend').html(html);
                } else {
                    $('#possible-friend .friends-empty').remove();
                    $('#possible-friend').append(html);
                }
            },
        });
    }

    $(document).on('click', '.js-hide-possible-friend', function () {
        const num = $(this).attr('data-num');
        $('.possible-friend-cart[data-num="' + num + '"]').remove();
        loadPossibleFriends(false);
    });

    $(document).on('click', '#show-possible_friends', function () {
        loadPossibleFriends(true);
    });

    window.showMoreFriend = function (userId) {
        if (moreFriend.loading) {
            return false;
        }

        moreFriend.loading = true;

        $.ajax({
            type: 'POST',
            url: ajaxUrl('get_friends_list'),
            dataType: 'json',
            data: {
                _token: csrfToken,
                number: moreFriend.number,
                offset: moreFriend.offset,
                user_id: userId,
            },
            success: function (data) {
                const items = Array.isArray(data.item) ? data.item : [];

                if (!items.length) {
                    $('#show_more_friends').hide();
                    return;
                }

                $('#friends .friends-empty').remove();
                $('#friends').append(items.map(function (item) {
                    return renderFriend(item, null, true);
                }).join(''));

                moreFriend.offset += moreFriend.number;

                if (items.length < moreFriend.number) {
                    $('#show_more_friends').hide();
                }
            },
            complete: function () {
                moreFriend.loading = false;
            },
            error: function () {
                $('#show_more_friends').hide();
            },
        });

        return false;
    };

    window.accept_friendship = function (id) {
        $.ajax({
            type: 'POST',
            url: ajaxUrl('accept_friendship'),
            dataType: 'json',
            data: {
                _token: csrfToken,
                user_id: id,
            },
            success: function (data) {
                if (Number(data.status) === 1) {
                    location.reload();
                }
            },
        });

        return false;
    };

    window.remove_friend = function (id) {
        $.ajax({
            type: 'POST',
            url: ajaxUrl('remove_friend'),
            dataType: 'json',
            data: {
                _token: csrfToken,
                user_id: id,
            },
            success: function (data) {
                if (data.result === 'success' || data.status === 'success') {
                    location.reload();
                }
            },
        });

        return false;
    };

    window.add_as_friend = function (id) {
        $.ajax({
            type: 'POST',
            url: ajaxUrl('add_as_friend'),
            dataType: 'json',
            data: {
                _token: csrfToken,
                user_id: id,
            },
            success: function (data) {
                if (data.status !== null) {
                    location.reload();
                }
            },
        });

        return false;
    };
})(jQuery);
