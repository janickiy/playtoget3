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
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        return Math.ceil(bytes / 1024) + ' KB';
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

    function albumUrl(albumId) {
        return redirectBase.replace(/\/$/, '') + '/' + encodeURIComponent(albumId);
    }

    function showUploadCompleteModal(albumId) {
        const url = albumUrl(albumId);
        const link = '<a class="photo-upload-back-link" href="' + url + '">Back to photo album</a>';

        if (typeof $.confirm === 'function') {
            $.confirm({
                title: 'Photos uploaded',
                message: 'Photos were uploaded successfully.<br>' + link,
                buttons: {
                    Close: {
                        'class': 'gray',
                        action: function () {}
                    }
                }
            });

            return;
        }

        window.location = url;
    }

    function removeFile(id) {
        queue = queue.filter(function (item) {
            return item.id !== id;
        });

        $('#' + id).remove();

        if (!queue.length) {
            setStatus('Select one or more photos.');
            return;
        }

        setStatus('Files in queue: ' + queue.length + '.');
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
                    '<span class="icons-hid">' +
                        '<button type="button" class="photo-upload-remove no_attach" data-tooltip="Remove photo" data-num="' + id + '" aria-label="Remove photo">' +
                            '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
                                '<path d="M6 11h12v2H6z"></path>' +
                            '</svg>' +
                        '</button>' +
                    '</span>' +
                '</div>' +
                '<div class="photo-upload-meta">' +
                    '<div class="photo-upload-name">' + escapeHtml(file.name) + ' <span>' + formatSize(file.size) + '</span></div>' +
                    '<textarea class="form-control comment_attach input_hastags" placeholder="Description" data-num="' + hashId + '"></textarea>' +
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
                setStatus('File "' + file.name + '" was not added: only JPG, PNG, and GIF are allowed.', 'error');
                return;
            }

            if (file.size > maxFileSize) {
                setStatus('File "' + file.name + '" was not added: size exceeds 32 MB.', 'error');
                return;
            }

            renderFile(file);
            added++;
        });

        if (added) {
            setStatus('Files in queue: ' + queue.length + '.');
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
                    reject('Invalid server response.');
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && response.info && !response.error) {
                    setProgress(item.id, 100);
                    $item.addClass('photo-upload-item-success');
                    $item.find('.photo-upload-message').text('Uploaded');
                    resolve(response);
                    return;
                }

                reject(response.error || 'Upload error.');
            };

            xhr.onerror = function () {
                reject('Server connection error.');
            };

            xhr.send(formData);
        });
    }

    function uploadQueue() {
        const albumId = $('select[name=photoalbum_id]').val();
        let current = 0;
        let failed = 0;

        if (!queue.length) {
            setStatus('Select photos first.', 'error');
            return;
        }

        if (!albumId) {
            setStatus('Choose an album for upload.', 'error');
            return;
        }

        $uploadButton.addClass('disabled').text('Loading...');
        $pickButton.addClass('disabled');
        setStatus('Uploading photos...');

        function next() {
            if (current >= queue.length) {
                if (failed) {
                    $uploadButton.removeClass('disabled').text('Upload photo');
                    $pickButton.removeClass('disabled');
                    setStatus('Upload completed with errors. Not uploaded: ' + failed + '.', 'error');
                    return;
                }

                setStatus('Photos uploaded.', 'success');
                $uploadButton.removeClass('disabled').text('Upload photo');
                $pickButton.removeClass('disabled');
                queue = [];
                showUploadCompleteModal(albumId);
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
        setStatus('Your browser does not support HTML5 file uploads.', 'error');
        $pickButton.addClass('disabled');
        $uploadButton.addClass('disabled');
        return;
    }

    $pickButton.on('click', function () {
        if (!$(this).hasClass('disabled')) {
            $fileInput.trigger('click');
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
