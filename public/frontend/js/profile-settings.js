(function ($) {
    'use strict';

    var blankImage = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
    var saveWindowVisibleMs = 5000;
    var croppers = {
        avatar: {
            api: null,
            file: null,
            naturalSize: { width: 0, height: 0 },
            ajaxFileName: 'avatar',
            ajaxUrl: '/ajax/upload_avatar',
            aspectRatio: 1,
            input: '#profile-avatar-input',
            modal: '#avatar-crop-modal',
            overlay: '#avatar-crop-overlay',
            loading: '#avatar-crop-loading',
            selectButton: '#avatar-select-button',
            form: '#avatar-crop-form',
            stage: '.avatar-crop-stage',
            target: '#avatar-crop-target',
            hiddenFile: '#profile-avatar-file',
            x: '#avatar-crop-x',
            y: '#avatar-crop-y',
            w: '#avatar-crop-w',
            h: '#avatar-crop-h',
            minOriginalWidth: 100,
            minOriginalHeight: 100,
            minDisplaySize: [80, 80],
            maxDisplayWidth: 640,
            reservedHeight: 248,
            successText: 'Avatar is ready. Click Apply'
        },
        cover: {
            api: null,
            file: null,
            naturalSize: { width: 0, height: 0 },
            ajaxFileName: 'cover',
            ajaxUrl: '/ajax/upload_cover',
            aspectRatio: 1200 / 350,
            input: '#profile-cover-input',
            modal: '#cover-crop-modal',
            overlay: '#cover-crop-overlay',
            loading: '#cover-crop-loading',
            selectButton: '#cover-select-button',
            form: '#cover-crop-form',
            stage: '.cover-crop-stage',
            target: '#cover-crop-target',
            hiddenFile: '#profile-cover-file',
            x: '#cover-crop-x',
            y: '#cover-crop-y',
            w: '#cover-crop-w',
            h: '#cover-crop-h',
            minOriginalWidth: 300,
            minOriginalHeight: 80,
            minDisplaySize: [140, 40],
            maxDisplayWidth: 760,
            reservedHeight: 246,
            successText: 'Cover is ready. Click Apply'
        }
    };

    function showFail(message) {
        var $message = $('<div class="save_window_fail"></div>').text(message);

        $('.save_window_fail').remove();
        $('body').append($message);

        window.setTimeout(function () {
            $message.addClass('hiden');
        }, saveWindowVisibleMs);
    }

    function showOk(message) {
        var $message = $('<div class="save_window_ok"></div>').text(message);

        $('.save_window_ok').remove();
        $('body').append($message);

        window.setTimeout(function () {
            $message.addClass('hiden');
        }, saveWindowVisibleMs);
    }

    function normalizeTabName(tab) {
        var allowedTabs = ['profile', 'contacts', 'privacy', 'notifications', 'security', 'blacklist'];

        tab = (tab || '').toString().replace(/^#/, '');

        return $.inArray(tab, allowedTabs) !== -1 ? tab : 'profile';
    }

    function activeTabIndex($links, tabName) {
        var index = 0;

        $links.each(function (currentIndex) {
            if ($(this).attr('href') === '#' + tabName) {
                index = currentIndex;
                return false;
            }
        });

        return index;
    }

    function syncDeleteAccountForm(tabName) {
        $('.settings-delete-account-form').toggle(normalizeTabName(tabName) === 'profile');
    }

    function setActiveTabValue(tabName) {
        tabName = normalizeTabName(tabName);

        $('#profile-settings-active-tab').val(tabName);
        syncDeleteAccountForm(tabName);
    }

    function initTabs() {
        var $tabs = $('#tabs');

        if (!$tabs.length) {
            return;
        }

        var $links = $tabs.find('> ul > li > a');
        var $panels = $tabs.find('> div');
        var initialTab = normalizeTabName(window.location.hash || $('#profile-settings-active-tab').val());
        var initialIndex = activeTabIndex($links, initialTab);

        if ($.fn.tabs) {
            $tabs.tabs({
                active: initialIndex,
                activate: function (event, ui) {
                    setActiveTabValue(ui.newPanel.attr('id'));
                }
            });
            setActiveTabValue($panels.eq($tabs.tabs('option', 'active')).attr('id'));
            return;
        }

        var $initialLink = $links.eq(initialIndex);

        $panels.hide();
        $($initialLink.attr('href')).show();
        $initialLink.parent().addClass('active');
        setActiveTabValue(initialTab);

        $links.on('click', function (event) {
            event.preventDefault();

            $tabs.find('> ul > li').removeClass('active');
            $(this).parent().addClass('active');
            $panels.hide();
            $($(this).attr('href')).show();
            setActiveTabValue($(this).attr('href'));
        });
    }

    function config(type) {
        return croppers[type];
    }

    function setLoading(type, message) {
        var $loading = $(config(type).loading);

        if (!message) {
            $loading.fadeOut(120);
            return;
        }

        $loading.html(message).fadeIn(120);
    }

    function destroyCrop(type) {
        var cropper = config(type);

        if (cropper.api) {
            cropper.api.destroy();
            cropper.api = null;
        }

        $(cropper.modal).removeClass('has-image');
        $(cropper.stage).removeAttr('style');
        $(cropper.target)
            .attr('src', blankImage)
            .removeAttr('style');
        $(cropper.x + ', ' + cropper.y + ', ' + cropper.w + ', ' + cropper.h).val(0);
    }

    function openCrop(type) {
        var cropper = config(type);

        cropper.file = null;
        $(cropper.input).val('');
        destroyCrop(type);
        $(cropper.overlay).fadeIn(150);
        $(cropper.modal).fadeIn(150);
        $('body').css('overflow', 'hidden');
        setLoading(type, '');
    }

    function closeCrop(type) {
        var cropper = config(type);

        $(cropper.overlay).fadeOut(150);
        $(cropper.modal).fadeOut(150);
        $('body').css('overflow', 'auto');
    }

    function scaledCropCoords(type, coords) {
        var cropper = config(type);
        var $target = $(cropper.target);
        var displayWidth = $target.width() || 1;
        var displayHeight = $target.height() || 1;
        var scaleX = cropper.naturalSize.width / displayWidth;
        var scaleY = cropper.naturalSize.height / displayHeight;

        return {
            x: Math.max(0, Math.round(coords.x * scaleX)),
            y: Math.max(0, Math.round(coords.y * scaleY)),
            w: Math.max(0, Math.round(coords.w * scaleX)),
            h: Math.max(0, Math.round(coords.h * scaleY))
        };
    }

    function updateCropCoords(type, coords) {
        var cropper = config(type);
        var scaled = scaledCropCoords(type, coords);

        $(cropper.x).val(scaled.x);
        $(cropper.y).val(scaled.y);
        $(cropper.w).val(scaled.w);
        $(cropper.h).val(scaled.h);
    }

    function cropBounds(type) {
        var cropper = config(type);
        var $modal = $(cropper.modal);
        var modalTop = parseInt($modal.css('top'), 10) || 24;
        var modalWidth = $modal.innerWidth() || 700;
        var horizontalPadding = 48;
        var availableHeight = $(window).height() - (modalTop * 2) - cropper.reservedHeight;

        return {
            width: Math.max(260, Math.min(cropper.maxDisplayWidth, modalWidth - horizontalPadding)),
            height: Math.max(130, availableHeight)
        };
    }

    function fitCropTarget(type) {
        var cropper = config(type);
        var bounds = cropBounds(type);
        var ratio = Math.min(
            bounds.width / cropper.naturalSize.width,
            bounds.height / cropper.naturalSize.height,
            1
        );
        var displayWidth = Math.max(1, Math.round(cropper.naturalSize.width * ratio));
        var displayHeight = Math.max(1, Math.round(cropper.naturalSize.height * ratio));

        $(cropper.target).css({
            height: displayHeight + 'px',
            maxHeight: 'none',
            maxWidth: 'none',
            width: displayWidth + 'px'
        });

        $(cropper.stage).css({
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

    function initialSelection(type, displaySize) {
        var cropper = config(type);
        var width;
        var height;
        var x;
        var y;

        if (type === 'avatar') {
            width = Math.min(displaySize.width, displaySize.height, 360);
            height = width;
        } else {
            width = Math.min(displaySize.width * 0.9, displaySize.width);
            height = width / cropper.aspectRatio;

            if (height > displaySize.height * 0.7) {
                height = displaySize.height * 0.7;
                width = height * cropper.aspectRatio;
            }
        }

        width = Math.max(1, Math.round(width));
        height = Math.max(1, Math.round(height));
        x = Math.max(0, Math.round((displaySize.width - width) / 2));
        y = Math.max(0, Math.round((displaySize.height - height) / 2));

        return [x, y, x + width, y + height];
    }

    function decorateCropUi(type) {
        var cropper = config(type);
        var $selection = cropper.api && cropper.api.ui ? cropper.api.ui.selection : $();
        var $holder = cropper.api && cropper.api.ui ? cropper.api.ui.holder : $();

        if (type === 'avatar') {
            $holder.addClass('jcrop-avatar-circle');
            $selection.addClass('jcrop-avatar-selection');
            return;
        }

        $holder.addClass('jcrop-cover-rect');
        $selection.addClass('jcrop-cover-selection');
    }

    function initCrop(type, imageUrl) {
        var cropper = config(type);
        var $target = $(cropper.target);

        destroyCrop(type);

        $target.one('load', function () {
            var image = this;
            var displaySize;

            cropper.naturalSize = {
                width: image.naturalWidth || image.width,
                height: image.naturalHeight || image.height
            };

            $(cropper.modal).addClass('has-image');
            displaySize = fitCropTarget(type);

            $target.Jcrop({
                addClass: type === 'avatar' ? 'jcrop-avatar-circle' : 'jcrop-cover-rect',
                aspectRatio: cropper.aspectRatio,
                bgColor: 'black',
                bgOpacity: 0.45,
                minSize: cropper.minDisplaySize,
                setSelect: initialSelection(type, displaySize),
                onChange: function (coords) {
                    updateCropCoords(type, coords);
                },
                onSelect: function (coords) {
                    updateCropCoords(type, coords);
                }
            }, function () {
                cropper.api = this;
                decorateCropUi(type);
                window.setTimeout(function () {
                    decorateCropUi(type);
                }, 0);
                updateCropCoords(type, cropper.api.tellSelect());
            });
        });

        $target.attr('src', imageUrl);
    }

    function chooseCropFile(type, input) {
        var cropper = config(type);
        var file = input.files && input.files[0] ? input.files[0] : null;
        var reader;

        if (!file) {
            return;
        }

        if (!/^image\/(jpeg|png)$/i.test(file.type)) {
            input.value = '';
            cropper.file = null;
            showFail('Only JPG or PNG can be uploaded');
            return;
        }

        cropper.file = file;
        setLoading(type, '<img border="0" src="/frontend/images/select2-spinner.gif" width="20" alt="">');

        reader = new FileReader();
        reader.onload = function (event) {
            setLoading(type, '');
            initCrop(type, event.target.result);
        };
        reader.onerror = function () {
            setLoading(type, 'File reading error');
        };
        reader.readAsDataURL(file);
    }

    function updatePreviews(type, response) {
        var url = response.url + '?' + Math.random();

        if (type === 'avatar') {
            $('#preview_ava').attr('src', url);
            $('.mini_thumb_avatar img').attr('src', url);
            return;
        }

        $('#preview_cover').attr('src', url);
        $('.cover-photo').attr('src', url);
    }

    function saveCrop(type, event) {
        var cropper = config(type);
        var formData;
        var token;
        var width = parseInt($(cropper.w).val(), 10);
        var height = parseInt($(cropper.h).val(), 10);

        event.preventDefault();

        if (!cropper.file) {
            showFail('Choose a file');
            return false;
        }

        if (!width || !height || width < cropper.minOriginalWidth || height < cropper.minOriginalHeight) {
            showFail('The selected area is too small');
            return false;
        }

        token = $('meta[name="csrf-token"]').attr('content');
        formData = new FormData();
        formData.append('_token', token);
        formData.append(cropper.ajaxFileName, cropper.file);
        formData.append('x', $(cropper.x).val());
        formData.append('y', $(cropper.y).val());
        formData.append('w', $(cropper.w).val());
        formData.append('h', $(cropper.h).val());

        setLoading(type, '<img border="0" src="/frontend/images/select2-spinner.gif" width="20" alt="">');

        $.ajax({
            url: cropper.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (!response || response.result !== 'success') {
                    setLoading(type, response && response.error ? response.error : 'Image processing error');
                    return;
                }

                $(cropper.hiddenFile).val(response.file);
                updatePreviews(type, response);
                setLoading(type, '');
                closeCrop(type);
                showOk(cropper.successText);
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'Image processing error';

                setLoading(type, message);
            }
        });

        return false;
    }

    $(function () {
        initTabs();

        $('#profile-settings-form').on('submit', function () {
            var $tabs = $('#tabs');
            var activePanelId = '';

            if ($.fn.tabs && $tabs.data('ui-tabs')) {
                activePanelId = $tabs.find('> div').eq($tabs.tabs('option', 'active')).attr('id');
            } else {
                activePanelId = $tabs.find('> ul > li.active > a').attr('href');
            }

            setActiveTabValue(activePanelId);
        });

        $('#profile-avatar-input').on('change', function () {
            chooseCropFile('avatar', this);
        });

        $('#profile-cover-input').on('change', function () {
            chooseCropFile('cover', this);
        });

        $(document).on('click', '.top_thumb_avatar img.editable-avatar', function () {
            openCrop('avatar');
        });

        $(document).on('click', '.cover_page.editable-cover-area', function (event) {
            if ($(event.target).is('input')) {
                return;
            }

            openCrop('cover');
        });

        $('#avatar-select-button').on('click', function () {
            $('#profile-avatar-input').trigger('click');
        });

        $('#cover-select-button').on('click', function () {
            $('#profile-cover-input').trigger('click');
        });

        $('#avatar-crop-form').on('submit', function (event) {
            saveCrop('avatar', event);
        });

        $('#cover-crop-form').on('submit', function (event) {
            saveCrop('cover', event);
        });

        $('.avatar-crop-close').on('click', function () {
            closeCrop($(this).closest('.avatar-crop-modal').data('type'));
        });

        $('#avatar-crop-overlay, #cover-crop-overlay').on('click', function (event) {
            if (event.target !== this) {
                return;
            }

            closeCrop($(this).find('.avatar-crop-modal').data('type'));
        });
    });
})(jQuery);
