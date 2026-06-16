let messagePollTimer = null;
let messagePollPrimed = false;
let lastInboxMessageId = 0;
let pushPollTimer = null;
let pushPollPrimed = false;
let pushToastTimer = null;
let pushToastShowing = false;
const pushToastQueue = [];
const messagePollInterval = 10000;
const pushPollInterval = 10000;
const pushSeenLimit = 200;

function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
}

function ajaxActionUrl(action) {
    const base = String(window.profileAjaxBase || '/ajax').replace(/\/$/, '');

    return base + '/' + action;
}

function profileUrl(userId) {
    const base = String(window.profileBase || '/profile').replace(/\/$/, '');

    return base + '/' + encodeURIComponent(userId);
}

function dialogueUrl(userId) {
    const base = String(window.dialoguesBase || (window.user ? '/profile/' + window.user + '/messages/user' : '/profile')).replace(/\/$/, '');

    return base + '/' + encodeURIComponent(userId);
}

function currentUserId() {
    return parseInt(window.user, 10) || 0;
}

function pushSeenStorageKey() {
    return 'playtoget_push_seen_' + currentUserId();
}

function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}

function readPushSeenStore() {
    try {
        const parsed = JSON.parse(localStorage.getItem(pushSeenStorageKey()) || '{}');

        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        return {};
    }
}

function writePushSeenStore(store) {
    try {
        const entries = Object.entries(store || {})
            .sort(function (left, right) {
                return (right[1] || 0) - (left[1] || 0);
            })
            .slice(0, pushSeenLimit);
        const limitedStore = {};

        $.each(entries, function (_, entry) {
            limitedStore[entry[0]] = entry[1];
        });

        localStorage.setItem(pushSeenStorageKey(), JSON.stringify(limitedStore));
    } catch (error) {}
}

function isPushSeen(key) {
    if (!key) {
        return true;
    }

    return Object.prototype.hasOwnProperty.call(readPushSeenStore(), key);
}

function markPushSeen(key) {
    if (!key) {
        return;
    }

    const store = readPushSeenStore();
    store[key] = Date.now();
    writePushSeenStore(store);
}

function markPushItemsSeen(items) {
    const store = readPushSeenStore();
    const time = Date.now();

    $.each(items || [], function (_, item) {
        if (item && item.key) {
            store[item.key] = time;
        }
    });

    writePushSeenStore(store);
}

function messageId(row) {
    return parseInt(row.id_message || row.id || 0, 10) || 0;
}

function rememberLastInboxId(rows) {
    $.each(rows || [], function (_, row) {
        lastInboxMessageId = Math.max(lastInboxMessageId, messageId(row));
    });
}

function applyMessageEmotions() {
    $('.message-text').each(function () {
        $(this).emotions();
    });

    $('.message-reply-text').each(function () {
        $(this).emotions();
    });
}

function setMessageCount(count) {
    const value = parseInt(count, 10) || 0;
    const counter = $('#message_count');

    if (!counter.length) {
        return;
    }

    counter.html(value);

    if (value > 0) {
        counter.removeClass('displayNone').fadeIn();
    } else {
        counter.addClass('displayNone').hide();
    }
}

function playMessageSound() {
    const audio = new Audio();
    audio.preload = 'auto';
    audio.src = '/frontend/audio/message.mp3';

    const result = audio.play();
    if (result && typeof result.catch === 'function') {
        result.catch(function () {});
    }
}

function showPushToast(text, url, complete) {
    const toast = $('.window-message');

    if (!toast.length) {
        if (typeof complete === 'function') {
            complete();
        }

        return;
    }

    const content = url
        ? '<a href="' + escapeAttribute(url) + '">' + escapeHtml(text) + '</a>'
        : '<span>' + escapeHtml(text) + '</span>';

    clearTimeout(pushToastTimer);
    toast.stop(true, true).html(content).addClass('is-visible').fadeIn(180);

    pushToastTimer = setTimeout(function () {
        toast.fadeOut(500, function () {
            toast.removeClass('is-visible').empty();

            if (typeof complete === 'function') {
                complete();
            }
        });
    }, 3000);
}

function showNextPushToast() {
    if (pushToastShowing || !pushToastQueue.length) {
        return;
    }

    const item = pushToastQueue.shift();
    pushToastShowing = true;
    playMessageSound();
    showPushToast(item.text, item.url, function () {
        pushToastShowing = false;
        setTimeout(showNextPushToast, 250);
    });
}

function enqueuePushNotification(item) {
    if (!item || !item.text) {
        return;
    }

    pushToastQueue.push(item);
    showNextPushToast();
}

function messageSenderName(row) {
    const name = $.trim([row.firstname, row.lastname].join(' '));

    return name || 'Пользователь';
}

function messageNotificationText(row) {
    return messageSenderName(row) + ' отправил(а) Вам новое личное сообщение';
}

function renderIncomingMessage(row) {
    let message = '<div class="message-reply" id="message-' + messageId(row) + '">';
    message += '<div class="message ">';
    message += '<div class="message-account">';
    message += '<img src="' + row.avatar + '" alt="" class="img-account">';
    message += '<h5 class="name"><a href="' + profileUrl(row.sender_id) + '">' + row.firstname + ' ' + row.lastname + '</a></h5>';
    message += '<p class="data">' + row.created + '</p>';
    message += '</div>';
    message += '<p class="message-reply-text">' + row.content + '<br>';
    message += row.image + '</p>';
    message += '</div>';
    message += '</div>';

    return message;
}

function updateDialogPreview(row) {
    const dialog = $('div').is('#old_dialogue');
    const dialogExists = $('div').is('.dialogues[data-num=' + row.sender_id + ']');

    if (!dialog) {
        return false;
    }

    $('#old_dialogue').find('.no_dialogues').remove();

    if (!dialogExists) {
        let dialogues = '<div class="row dialogues " data-num=' + row.sender_id + '>';
        dialogues += '<div class="col-md-4">';
        dialogues += '<a href="' + profileUrl(row.sender_id) + '">';
        dialogues += '<img src="' + row.avatar + '" width="50" alt="" class="img-account" style="float: left;">';
        dialogues += '<div class="fromwho">' + row.firstname + '<br>' + row.lastname + '<br>';
        dialogues += '<span>' + row.created + '</span></div>';
        dialogues += '</a></div>';
        dialogues += '<div class="col-md-8 ">';
        dialogues += '<a href="' + dialogueUrl(row.sender_id) + '" >';
        dialogues += '<img src="' + row.avatar + '" alt="" class="img-mess-dialog">';
        dialogues += '<span class="ahref status_red ">' + row.content + '</span>';
        dialogues += '</a></div></div>';
        $('.container_dialog').prepend(dialogues);
    } else {
        $('.dialogues[data-num=' + row.sender_id + ']').find('.ahref').html(row.content);
    }

    $('.href').each(function () {
        $(this).emotions();
    });

    return true;
}

function showMessagePopup(row) {
    enqueuePushNotification({
        key: 'message:' + messageId(row),
        type: 'message',
        text: messageNotificationText(row),
        url: dialogueUrl(row.sender_id)
    });
}

function handleIncomingMessage(row, notify) {
    const id = messageId(row);
    const selectedDialogUser = $('#message-list').attr('data-num');

    if (!id || $('#message-' + id).length) {
        return;
    }

    if (selectedDialogUser && String(selectedDialogUser) === String(row.sender_id)) {
        $('#message-list').append(renderIncomingMessage(row));
        $('.mess_list').find('.no_message').remove();
        $('.mess_list').animate({scrollTop: 1000000}, 1100);
        applyMessageEmotions();
    } else {
        updateDialogPreview(row);
    }

    if (notify) {
        showMessagePopup(row);
    }
}

function pollIncomingMessages() {
    if (!window.user) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: ajaxActionUrl('get_new_messages'),
        dataType: 'json',
        data: {
            _token: csrfToken(),
            last_id: lastInboxMessageId
        },
        success: function (data) {
            const rows = data && data.item ? data.item : [];

            if (typeof data.count !== 'undefined') {
                setMessageCount(data.count);
            }

            if (!messagePollPrimed) {
                rememberLastInboxId(rows);
                messagePollPrimed = true;
                return;
            }

            $.each(rows, function (_, row) {
                rememberLastInboxId([row]);
                handleIncomingMessage(row, true);
            });
        }
    });
}

function pollPushNotifications() {
    if (!window.user) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: ajaxActionUrl('get_push_notifications'),
        dataType: 'json',
        data: {
            _token: csrfToken()
        },
        success: function (data) {
            const items = data && data.items ? data.items : [];

            if (!pushPollPrimed) {
                markPushItemsSeen(items);
                pushPollPrimed = true;
                return;
            }

            $.each(items, function (_, item) {
                if (!item || !item.key || isPushSeen(item.key)) {
                    return;
                }

                markPushSeen(item.key);
                enqueuePushNotification(item);
            });
        }
    });
}

function initPushNotifications() {
    if (pushPollTimer || !window.user) {
        return;
    }

    pollPushNotifications();
    pushPollTimer = setInterval(pollPushNotifications, pushPollInterval);
}

function initHeaderPolling() {
    if (messagePollTimer) {
        return;
    }

    pollIncomingMessages();
    initPushNotifications();
    messagePollTimer = setInterval(pollIncomingMessages, messagePollInterval);
}

window.initHeaderPolling = initHeaderPolling;
window.initPushNotifications = initPushNotifications;


$(document).ready(function () {
    initHeaderPolling();

    $(document).on('keyup', '#main_search', function () {
        const text = $(this).val();
        if (text != '') {
            $(this).addClass('white');
        } else {
            $(this).removeClass('white');
        }
    })
});
