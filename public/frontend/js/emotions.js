const smile_string = ":-) :-( ;-) :-p :-* :-! :`( :-/ 8-) :ireful: :rofl: :banned: :nhl: :fan: :warning: :training: :aikido: :rotest: :soccer: :basketball: :tennis: :medal: :cycling: :thumbsup: :heart: :ok: :fire: :party: :think: :wow: :sleep: :love: :victory:";

function readFile(file, num, a) {
    const FR = new FileReader();
    FR.onload = function (e) {
        $('.files_block[data-num=' + num + ']').prepend('<div class="attach attach-preview" data-num=' + a + '><img src="' + e.target.result + '" alt=""><div class="percent"><p>0%</p></div></div>');
    };
    FR.readAsDataURL(file);
}


$(document).ready(function () {

    const smiles = $(".smilesChoose");
    const inputEl = $("#comment");
    const smilesBtn = $("#smilesBtn");
    const messages = $("div.chat-messages");

    $('div.chat-message').emotions();

    $('.message-text').each(function () {
        $(this).emotions();
    })
    $('.message-reply-text').each(function () {
        $(this).emotions();
    })
    $('.href').each(function () {
        $(this).emotions();
    })
    $('.article').each(function () {
        $(this).emotions();
    })

    $(document).on("click", ".smilesChoose span", function () {
        const num = $(this).parent('div').attr('data-num');
        const shortCode = $.emotions.shortcode($(this).attr("title"));
        $("#comment[data-num='" + num + "']").val($("#comment[data-num='" + num + "']").val() + " " + shortCode + " ");
        $("#message").val($("#message").val() + " " + shortCode + " ");

        //  $(".smilesChoose[data-num='"+num+"']").toggle();
        $("#comment[data-num='" + num + "']").trigger('focus');
        $("#message").trigger('focus');
    });


    function smilesPanel(button) {
        const localPanel = button.closest('.smile-files').find('.smilesChoose').first();

        return localPanel.length
            ? localPanel
            : $(".smilesChoose[data-num='" + button.attr('data-num') + "']").first();
    }

    function renderSmiles(panel) {
        if (!panel.data('rendered')) {
            panel.html(smile_string);
            panel.emotions();
            panel.data('rendered', true);
        }
    }

    $(document).on("click", ".smilesBtn", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const panel = smilesPanel($(this));

        if (!panel.length) {
            return;
        }

        renderSmiles(panel);
        $(".smilesChoose").not(panel).removeClass('is-open').hide();
        panel.toggleClass('is-open').stop(true, true).fadeToggle(120);
    });

    $(document).on("click", ".smilesChoose", function (event) {
        event.stopPropagation();
    });


    $("#sendBtn").on('click', function () {
        processMessage();
    });


    $(document).on("click", ".files", function () {
        const num = $(this).attr('data-num');
        $('.file_name[data-num=' + num + ']').trigger('click');
        return false;
    })

    $(document).on('change', '.file_name', function () {
        const num = $(this).attr('data-num');
        let input = $(this).closest('form').find('textarea');
        if (input.length == 0) {
            input = $(this).closest('form').find('#comment')
        }
        let mess = input.val();
        const then = $(this).closest('form');
        const form = then[0];
        const files = $(this)[0].files;
        const base64 = '';
        let id_album;
        let error = true;
        if (files.length > 5) {
            form.reset();
            input.val(mess);
            $('.save_window_fail').html('Максимальное количество файлов 5').removeClass('hiden');
            setTimeout(function () {
                $('.save_window_fail').addClass('hiden');
            }, 2000);
            return false;
        } else {
            if (files.length > 0) {


                $('.files_block[data-num=' + num + ']').html('<div style="clear:both"></div>');
                for (let a = 0; a < files.length; a++) {
                    //console.log(this.files);
                    //$('.files_block[data-num='+num+']').append('<p><img src="./frontend/images/files.png" alt=""> '+this.files[a].name+' ('+this.files[a].size/1000+'kb)</p>');

                    const rFilter = /^(image\/jpeg|image\/png)$/i;
                    if (rFilter.test(files[a].type)) {
                        error = false;
                        readFile(files[a], num, a);
                        const window_h = $(window).height();
                        const mess_h = window_h - 410;
                        $('.mess_list').css('height', mess_h);
                        //readFile(files[a],num);
                        const formData = new FormData();
                        formData.append('photoalbumable_type', 'user_attach');
                        formData.append('file', files[a]);
                        formData.append('num', a);
                        const token = $('meta[name="csrf-token"]').attr('content');

                        if (token) {
                            formData.append('_token', token);
                        }

                        $.ajax({
                            url: '/ajax/add_photo_ajax_attach',
                            type: 'POST',
                            contentType: false,
                            processData: false,
                            data: formData,
                            dataType: 'json',
                            xhr: function () {
                                const xhr = $.ajaxSettings.xhr(); // получаем объект XMLHttpRequest
                                xhr.upload.addEventListener('progress', function (evt) { // добавляем обработчик события progress (onprogress)
                                    if (evt.lengthComputable) { // если известно количество байт
                                        // высчитываем процент загруженного
                                        const percentComplete = Math.ceil(evt.loaded / evt.total * 100);
                                        $('.attach').find('p').html(percentComplete + '%');
                                        // устанавливаем значение в атрибут value тега <progress>
                                        // и это же значение альтернативным текстом для браузеров, не поддерживающих <progress>
                                        console.log('Загружено ' + percentComplete + '%');
                                    }
                                }, false);
                                return xhr;
                            },
                            success: function (json) {
                                if (json) {
                                    console.log(json)
                                    $('.attach[data-num=' + json.num + ']').attr('data-id', json.message.id).find('.percent').remove();

                                }
                            }
                        });

                    } else {
                        form.reset();
                        input.val(mess);
                        $('.save_window_fail').html('Неверный формат файла').removeClass('hiden');
                        setTimeout(function () {
                            $('.save_window_fail').addClass('hiden');
                        }, 2000);
                    }


                }
                if (!error)
                    $('.files_block[data-num=' + num + ']').prepend('<p><a href="#" class="removeAttach">Удалить прикрепленные файлы</a></p>')
            }
        }
        //console.log(files.length);
    })


    $(document).on('click', '.removeAttach', function () {
        let input = $(this).closest('form').find('textarea');
        if (input.length == 0) {
            input = $(this).closest('form').find('#comment')
        }
        let mess = input.val();
        const then = $(this).closest('form');
        const form = then[0];
        form.reset();
        input.val(mess);
        $(this).closest('.files_block').html('');
        const window_h = $(window).height();
        const mess_h = window_h - 310;
        $('.mess_list').css('height', mess_h);
        return false;
    })


    $(document).on('click', function (e) { // событие клика по веб-документу
        const div = $('.smilesChoose'); // тут указываем ID элемента
        if (!div.is(e.target) // если клик был не по нашему блоку
            && div.has(e.target).length === 0) {
            div.removeClass('is-open').hide();

        }
    });


    document.onkeyup = function (e) {
        if ($('#message').is(":focus")) {
            e = e || window.event;
            if (e.keyCode === 13) {
                $('#addMessageForm').trigger('submit');
            }
        }
    }


});
