(function ($) {
    'use strict';

    var avatarCropApi = null;
    var avatarCropFile = null;
    var avatarNaturalSize = { width: 0, height: 0 };

    function showFail(message) {
        var $message = $('<div class="save_window_fail"></div>').text(message);

        $('.save_window_fail').remove();
        $('body').append($message);

        window.setTimeout(function () {
            $message.addClass('hiden');
        }, 2500);
    }

    function showOk(message) {
        var $message = $('<div class="save_window_ok"></div>').text(message);

        $('.save_window_ok').remove();
        $('body').append($message);

        window.setTimeout(function () {
            $message.addClass('hiden');
        }, 2500);
    }

    function initTabs() {
        var $tabs = $('#tabs');

        if (!$tabs.length) {
            return;
        }

        if ($.fn.tabs) {
            $tabs.tabs();
            return;
        }

        var $links = $tabs.find('> ul > li > a');
        var $panels = $tabs.find('> div');

        $panels.hide().first().show();
        $links.first().parent().addClass('active');

        $links.on('click', function (event) {
            event.preventDefault();

            $tabs.find('> ul > li').removeClass('active');
            $(this).parent().addClass('active');
            $panels.hide();
            $($(this).attr('href')).show();
        });
    }

    function previewSelectedImage(input, previewSelector) {
        var file = input.files && input.files[0] ? input.files[0] : null;

        if (!file) {
            return;
        }

        if (!/^image\/(jpeg|png)$/i.test(file.type)) {
            input.value = '';
            showFail('Можно загрузить только JPG или PNG');
            return;
        }

        var reader = new FileReader();

        reader.onload = function (event) {
            $(previewSelector).attr('src', event.target.result);
        };

        reader.readAsDataURL(file);
    }

    function setAvatarLoading(message) {
        var $loading = $('#avatar-crop-loading');

        if (!message) {
            $loading.fadeOut(120);
            return;
        }

        $loading.html(message).fadeIn(120);
    }

    function destroyAvatarCrop() {
        if (avatarCropApi) {
            avatarCropApi.destroy();
            avatarCropApi = null;
        }

        $('#avatar-crop-modal').removeClass('has-image');
        $('.avatar-crop-stage').removeAttr('style');
        $('#avatar-crop-target')
            .attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==')
            .removeAttr('style');
        $('#avatar-crop-x, #avatar-crop-y, #avatar-crop-w, #avatar-crop-h').val(0);
    }

    function openAvatarCrop() {
        avatarCropFile = null;
        $('#profile-avatar-input').val('');
        destroyAvatarCrop();
        $('#avatar-crop-overlay').fadeIn(150);
        $('#avatar-crop-modal').fadeIn(150);
        $('body').css('overflow', 'hidden');
        setAvatarLoading('');
    }

    function closeAvatarCrop() {
        $('#avatar-crop-overlay').fadeOut(150);
        $('#avatar-crop-modal').fadeOut(150);
        $('body').css('overflow', 'auto');
    }

    function scaledCropCoords(coords) {
        var $target = $('#avatar-crop-target');
        var displayWidth = $target.width() || 1;
        var displayHeight = $target.height() || 1;
        var scaleX = avatarNaturalSize.width / displayWidth;
        var scaleY = avatarNaturalSize.height / displayHeight;

        return {
            x: Math.max(0, Math.round(coords.x * scaleX)),
            y: Math.max(0, Math.round(coords.y * scaleY)),
            w: Math.max(0, Math.round(coords.w * scaleX)),
            h: Math.max(0, Math.round(coords.h * scaleY))
        };
    }

    function updateAvatarCoords(coords) {
        var scaled = scaledCropCoords(coords);

        $('#avatar-crop-x').val(scaled.x);
        $('#avatar-crop-y').val(scaled.y);
        $('#avatar-crop-w').val(scaled.w);
        $('#avatar-crop-h').val(scaled.h);
    }

    function avatarCropBounds() {
        var $modal = $('#avatar-crop-modal');
        var modalTop = parseInt($modal.css('top'), 10) || 24;
        var modalWidth = $modal.innerWidth() || 700;
        var horizontalPadding = 48;
        var reservedHeight = 248;

        return {
            width: Math.max(260, Math.min(640, modalWidth - horizontalPadding)),
            height: Math.max(220, $(window).height() - (modalTop * 2) - reservedHeight)
        };
    }

    function fitAvatarCropTarget() {
        var bounds = avatarCropBounds();
        var ratio = Math.min(
            bounds.width / avatarNaturalSize.width,
            bounds.height / avatarNaturalSize.height,
            1
        );
        var displayWidth = Math.max(1, Math.round(avatarNaturalSize.width * ratio));
        var displayHeight = Math.max(1, Math.round(avatarNaturalSize.height * ratio));

        $('#avatar-crop-target').css({
            height: displayHeight + 'px',
            maxHeight: 'none',
            maxWidth: 'none',
            width: displayWidth + 'px'
        });

        $('.avatar-crop-stage').css({
            height: displayHeight + 'px',
            maxHeight: bounds.height + 'px',
            maxWidth: bounds.width + 'px',
            width: displayWidth + 'px'
        });

        return {
            height: displayHeight,
            width: displayWidth
        };
    }

    function decorateAvatarCropUi() {
        var $selection = avatarCropApi && avatarCropApi.ui ? avatarCropApi.ui.selection : $();
        var $holder = avatarCropApi && avatarCropApi.ui ? avatarCropApi.ui.holder : $();

        $holder.addClass('jcrop-avatar-circle');
        $selection.addClass('jcrop-avatar-selection');
    }

    function initAvatarCrop(imageUrl) {
        var $target = $('#avatar-crop-target');

        destroyAvatarCrop();
        $target.attr('src', imageUrl);

        $target.one('load', function () {
            var image = this;
            var displaySize;
            var selectSize;
            var startX;
            var startY;

            avatarNaturalSize = {
                width: image.naturalWidth || image.width,
                height: image.naturalHeight || image.height
            };

            $('#avatar-crop-modal').addClass('has-image');
            displaySize = fitAvatarCropTarget();
            selectSize = Math.min(displaySize.width, displaySize.height, 360);
            startX = Math.max(0, Math.round((displaySize.width - selectSize) / 2));
            startY = Math.max(0, Math.round((displaySize.height - selectSize) / 2));

            $target.Jcrop({
                addClass: 'jcrop-avatar-circle',
                aspectRatio: 1,
                bgColor: 'black',
                bgOpacity: 0.45,
                minSize: [80, 80],
                setSelect: [startX, startY, startX + selectSize, startY + selectSize],
                onChange: updateAvatarCoords,
                onSelect: updateAvatarCoords
            }, function () {
                avatarCropApi = this;
                decorateAvatarCropUi();
                window.setTimeout(decorateAvatarCropUi, 0);
                updateAvatarCoords(avatarCropApi.tellSelect());
            });
        });
    }

    function chooseAvatarFile(input) {
        var file = input.files && input.files[0] ? input.files[0] : null;
        var reader;

        if (!file) {
            return;
        }

        if (!/^image\/(jpeg|png)$/i.test(file.type)) {
            input.value = '';
            avatarCropFile = null;
            showFail('Можно загрузить только JPG или PNG');
            return;
        }

        avatarCropFile = file;
        setAvatarLoading('<img border="0" src="/templates/images/select2-spinner.gif" width="20" alt="">');

        reader = new FileReader();
        reader.onload = function (event) {
            setAvatarLoading('');
            initAvatarCrop(event.target.result);
        };
        reader.onerror = function () {
            setAvatarLoading('Ошибка чтения файла');
        };
        reader.readAsDataURL(file);
    }

    function saveAvatarCrop(event) {
        var formData;
        var token;
        var width = parseInt($('#avatar-crop-w').val(), 10);
        var height = parseInt($('#avatar-crop-h').val(), 10);

        event.preventDefault();

        if (!avatarCropFile) {
            showFail('Выберите файл');
            return false;
        }

        if (!width || !height || width < 100 || height < 100) {
            showFail('Выделенная область слишком мала');
            return false;
        }

        token = $('meta[name="csrf-token"]').attr('content');
        formData = new FormData();
        formData.append('_token', token);
        formData.append('avatar', avatarCropFile);
        formData.append('x', $('#avatar-crop-x').val());
        formData.append('y', $('#avatar-crop-y').val());
        formData.append('w', $('#avatar-crop-w').val());
        formData.append('h', $('#avatar-crop-h').val());

        setAvatarLoading('<img border="0" src="/templates/images/select2-spinner.gif" width="20" alt="">');

        $.ajax({
            url: '/ajax/uploadavatar',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (!response || response.result !== 'success') {
                    setAvatarLoading(response && response.error ? response.error : 'Ошибка обработки изображения');
                    return;
                }

                $('#profile-avatar-file').val(response.file);
                $('#preview_ava').attr('src', response.url + '?' + Math.random());
                $('.mini_thumb_avatar img').attr('src', response.url + '?' + Math.random());
                setAvatarLoading('');
                closeAvatarCrop();
                showOk('Аватар подготовлен. Нажмите «Применить»');
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'Ошибка обработки изображения';

                setAvatarLoading(message);
            }
        });

        return false;
    }

    $(function () {
        initTabs();

        $('#profile-avatar-input').on('change', function () {
            chooseAvatarFile(this);
        });

        $('#profile-cover-input').on('change', function () {
            previewSelectedImage(this, '#preview_cover');
        });

        $(document).on('click', '.top_thumb_avatar img.editable-avatar', function () {
            openAvatarCrop();
        });

        $('#avatar-select-button').on('click', function () {
            $('#profile-avatar-input').trigger('click');
        });

        $('#avatar-crop-form').on('submit', saveAvatarCrop);

        $('.avatar-crop-close').on('click', closeAvatarCrop);

        $('#avatar-crop-overlay').on('click', function (event) {
            if (event.target === this) {
                closeAvatarCrop();
            }
        });

        $(document).on('click', '.cover_page.editable-cover-area', function (event) {
            if ($(event.target).is('input')) {
                return;
            }

            $('#profile-cover-input').trigger('click');
        });
    });
})(jQuery);
