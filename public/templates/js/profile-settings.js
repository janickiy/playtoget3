(function ($) {
    'use strict';

    function showFail(message) {
        var $message = $('<div class="save_window_fail"></div>').text(message);

        $('.save_window_fail').remove();
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

    $(function () {
        initTabs();

        $('#profile-avatar-input').on('change', function () {
            previewSelectedImage(this, '#preview_ava');
        });

        $('#profile-cover-input').on('change', function () {
            previewSelectedImage(this, '#preview_cover');
        });

        $(document).on('click', '.top_thumb_avatar img.editable-avatar', function () {
            $('#profile-avatar-input').trigger('click');
        });

        $(document).on('click', '.cover_page.editable-cover-area', function (event) {
            if ($(event.target).is('input')) {
                return;
            }

            $('#profile-cover-input').trigger('click');
        });
    });
})(jQuery);
