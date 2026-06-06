(function () {
    let queue = [];
    const uploadUrl = window.photoUploadUrl || '/ajax/add_photo_ajax';
    const redirectBase = window.photoAlbumRedirectBase || '/photoalbums';
    const maxFileSize = 32 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    const $fileInput = $('#photo-files');
    const $fileList = $('#filelist');
    const $status = $('#photo-upload-status');
    const $uploadButton = $('#uploadfiles');
    const $pickButton = $('#pickfiles');

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function supportsHtml5Upload() {
        return window.File && window.FileReader && window.FormData && window.XMLHttpRequest;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function formatSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1) + ' МБ';
        }

        return Math.ceil(bytes / 1024) + ' КБ';
    }

    function fileExtension(name) {
        return name.split('.').pop().toLowerCase();
    }

    function isImage(file) {
        const extension = fileExtension(file.name);

        return allowedExtensions.indexOf(extension) !== -1 &&
            (!file.type || allowedTypes.indexOf(file.type) !== -1);
    }

    function setStatus(text, type) {
        $status
            .removeClass('photo-upload-status-error photo-upload-status-success')
            .addClass(type ? 'photo-upload-status-' + type : '')
            .text(text);
    }

    function setProgress(id, percent) {
        const safePercent = Math.max(0, Math.min(100, percent));
        const $item = $('#' + id);

        $item.find('.photo-upload-progress-bar').css('width', safePercent + '%');
        $item.find('.photo-upload-percent').text(safePercent + '%');
    }

    function removeFile(id) {
        queue = queue.filter(function (item) {
            return item.id !== id;
        });

        $('#' + id).remove();

        if (!queue.length) {
            setStatus('Выберите одну или несколько фотографий.');
        }
    }

    function renderFile(file) {
        const id = 'photo-upload-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
        const hashId = Math.floor(Math.random() * 100000);
        const item = { id: id, file: file };
        queue.push(item);

        $fileList.append(
            '<div class="photo-upload-item" id="' + id + '">' +
                '<div class="attach big photo-upload-preview">' +
                    '<img alt="">' +
                    '<b><span class="photo-upload-percent">0%</span></b>' +
                    '<span class="icons-hid"><i class="no_attach" data-tooltip="Не добавлять" data-num="' + id + '">' +
                        '<img src="/frontend/images/icon-krest.png" alt="">' +
                    '</i></span>' +
                '</div>' +
                '<div class="photo-upload-meta">' +
                    '<div class="photo-upload-name">' + escapeHtml(file.name) + ' <span>' + formatSize(file.size) + '</span></div>' +
                    '<textarea class="form-control comment_attach input_hastags" placeholder="Комментарий к фото" data-num="' + hashId + '"></textarea>' +
                    '<div class="hashtags" data-num="' + hashId + '"></div>' +
                    '<div class="photo-upload-progress"><div class="photo-upload-progress-bar"></div></div>' +
                    '<div class="photo-upload-message"></div>' +
                '</div>' +
                '<div style="clear:both"></div>' +
            '</div>'
        );

        const reader = new FileReader();
        reader.onload = function (event) {
            $('#' + id).find('.photo-upload-preview img').attr('src', event.target.result);
        };
        reader.readAsDataURL(file);
    }

    function addFiles(files) {
        let added = 0;

        $.each(files, function (_, file) {
            if (!isImage(file)) {
                setStatus('Файл "' + file.name + '" не добавлен: допустимы только JPG, PNG и GIF.', 'error');
                return;
            }

            if (file.size > maxFileSize) {
                setStatus('Файл "' + file.name + '" не добавлен: размер больше 32 МБ.', 'error');
                return;
            }

            renderFile(file);
            added++;
        });

        if (added) {
            setStatus('Файлов в очереди: ' + queue.length + '.');
        }
    }

    function uploadFile(item) {
        return new Promise(function (resolve, reject) {
            const formData = new FormData();
            const $item = $('#' + item.id);
            const xhr = new XMLHttpRequest();

            formData.append('_token', csrfToken());
            formData.append('file', item.file);
            formData.append('categorie', $('select[name=photoalbum_id]').val());
            formData.append('photoalbumable_type', window.photoalbumableType || 'user');
            formData.append('description', $item.find('textarea').val());

            xhr.open('POST', uploadUrl, true);
            xhr.upload.onprogress = function (event) {
                if (event.lengthComputable) {
                    setProgress(item.id, Math.round((event.loaded / event.total) * 100));
                }
            };

            xhr.onload = function () {
                let response;

                try {
                    response = JSON.parse(xhr.responseText);
                } catch (error) {
                    reject('Некорректный ответ сервера.');
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && response.info && !response.error) {
                    setProgress(item.id, 100);
                    $item.addClass('photo-upload-item-success');
                    $item.find('.photo-upload-message').text('Загружено');
                    resolve(response);
                    return;
                }

                reject(response.error || 'Ошибка загрузки.');
            };

            xhr.onerror = function () {
                reject('Ошибка соединения с сервером.');
            };

            xhr.send(formData);
        });
    }

    function uploadQueue() {
        const albumId = $('select[name=photoalbum_id]').val();
        let current = 0;
        let failed = 0;

        if (!queue.length) {
            setStatus('Сначала выберите фотографии.', 'error');
            return;
        }

        if (!albumId) {
            setStatus('Выберите альбом для загрузки.', 'error');
            return;
        }

        $uploadButton.addClass('disabled').text('Загрузка...');
        $pickButton.addClass('disabled');
        setStatus('Загрузка файлов...');

        function next() {
            if (current >= queue.length) {
                if (failed) {
                    $uploadButton.removeClass('disabled').text('Загрузить файлы');
                    $pickButton.removeClass('disabled');
                    setStatus('Загрузка завершена с ошибками. Не загружено: ' + failed + '.', 'error');
                    return;
                }

                setStatus('Фотографии загружены.', 'success');
                window.location = redirectBase + '/' + albumId;
                return;
            }

            const item = queue[current++];
            uploadFile(item)
                .catch(function (message) {
                    failed++;
                    $('#' + item.id)
                        .addClass('photo-upload-item-error')
                        .find('.photo-upload-message')
                        .text(message);
                })
                .then(next);
        }

        next();
    }

    if (!supportsHtml5Upload()) {
        setStatus('Ваш браузер не поддерживает HTML5-загрузку файлов.', 'error');
        $pickButton.addClass('disabled');
        $uploadButton.addClass('disabled');
        return;
    }

    $pickButton.on('click', function () {
        if (!$(this).hasClass('disabled')) {
            $fileInput.click();
        }
    });

    $uploadButton.on('click', function () {
        if (!$(this).hasClass('disabled')) {
            uploadQueue();
        }
    });

    $fileInput.on('change', function () {
        addFiles(this.files);
        this.value = '';
    });

    $(document).on('click', '.no_attach', function () {
        if (!$uploadButton.hasClass('disabled')) {
            removeFile($(this).attr('data-num'));
        }
    });
})();
