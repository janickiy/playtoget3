@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends sport-block-show">
        <div class="container_in_swiper">
            <div class="swiper swiper-container gallery-top sport-block-slider">
                <div class="swiper-wrapper">
                    @forelse ($photos as $photo)
                        <div class="swiper-slide" style="background-image:url('{{ $photo['big'] ?: asset('frontend/images/default_group.png') }}')"></div>
                    @empty
                        <div class="swiper-slide" style="background-image:url('{{ asset('frontend/images/default_group.png') }}')"></div>
                    @endforelse
                </div>
                @if ($photos->count() > 1)
                    <div class="swiper-button-next sport-block-slider-next"></div>
                    <div class="swiper-button-prev sport-block-slider-prev"></div>
                @endif
            </div>
        </div>

        <div class="description_shop">
            <div class="text">
                <div class="text-container">
                    <p>{!! nl2br(e($data['about'])) !!}</p>
                </div>
                <a href="#" class="read_next" data-status="hide">Читать далее...</a>
            </div>
            <div class="contact">
                @if ($data['avatar'])
                    <img src="{{ $data['avatar'] }}" class="avatar_sport_block" alt="">
                @endif
                @if ($canEdit)
                    <a class="button_edit_groups" href="{{ route($routePrefix . '.edit', ['sportBlock' => $data['id']]) }}">Редактировать</a>
                @endif
                <h3>{{ $data['name'] }}</h3>
                @if ($data['address'])
                    <p class="adress">{{ $data['address'] }}</p>
                @endif
                @if ($data['phone'])
                    <p class="phone">{{ $data['phone'] }}</p>
                @endif
                @if ($data['email'])
                    <p class="mail email">{{ $data['email'] }}</p>
                @endif
                @if ($data['website'])
                    @php
                        $websiteUrl = \Illuminate\Support\Str::startsWith($data['website'], ['http://', 'https://'])
                            ? $data['website']
                            : 'http://' . $data['website'];
                    @endphp
                    <p class="site"><a href="{{ $websiteUrl }}" target="_blank">{{ $data['website'] }}</a></p>
                @endif
            </div>
        </div>

        @if ($canEdit && $uploadAlbum)
            <div id="filelist">Ваш браузер не поддерживает загрузку файлов.</div>
            <br>
            <div class="job_form">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div id="container" class="center_text marginTop20">
                            <button id="pickfiles" type="button" class="save-button">Добавить фото</button>
                            <button id="uploadfiles" type="button" class="save-button">Загрузить фото</button>
                        </div>
                        <div id="photo-upload-error" class="photo-upload-error" style="display:none"></div>
                    </div>
                </form>
            </div>
        @endif

    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/swiper.min.css') }}?v=9.4.1">
    <style>
        .sport-block-show #filelist {
            margin-top: 20px;
        }

        .sport-block-show .photo-upload-error {
            width: 70%;
            margin: 18px auto 0;
            padding: 12px 18px;
            border: 1px solid #f0c6c6;
            border-radius: 4px;
            background: #fff5f5;
            color: #c03a3a;
            font-size: 16px;
            text-align: center;
        }

        .sport-block-show .container_in_swiper {
            height: 365px;
            margin-top: 0;
            margin-bottom: 0;
            overflow: hidden;
            background: #eef4f4;
            border-radius: 4px 4px 0 0;
        }

        .sport-block-show .gallery-top,
        .sport-block-show .sport-block-slider {
            height: 365px;
            width: 100%;
        }

        .sport-block-show .sport-block-slider .swiper-slide {
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .sport-block-show .sport-block-slider-next,
        .sport-block-show .sport-block-slider-prev {
            --swiper-navigation-size: 20px;
            width: 44px !important;
            height: 44px;
            margin-top: -22px;
            border-radius: 50%;
            background: rgba(67, 86, 151, 0.92) !important;
            background-image: none !important;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(37, 43, 68, 0.22);
            transition: background 0.2s ease, opacity 0.2s ease;
        }

        .sport-block-show .sport-block-slider-next {
            right: 18px !important;
        }

        .sport-block-show .sport-block-slider-prev {
            left: 18px !important;
        }

        .sport-block-show .sport-block-slider-next:hover,
        .sport-block-show .sport-block-slider-prev:hover {
            background: #43aaa1 !important;
        }

        .sport-block-show .sport-block-slider-next::after,
        .sport-block-show .sport-block-slider-prev::after {
            font-size: 20px;
            font-weight: 700;
        }

        .sport-block-show .description_shop {
            border-radius: 0 0 4px 4px !important;
        }

        @media (max-width: 768px) {
            .sport-block-show .container_in_swiper,
            .sport-block-show .gallery-top,
            .sport-block-show .sport-block-slider {
                height: 250px !important;
            }
        }

        @media (max-width: 480px) {
            .sport-block-show .container_in_swiper,
            .sport-block-show .gallery-top,
            .sport-block-show .sport-block-slider {
                height: 200px !important;
            }

            .sport-block-show .sport-block-slider-next,
            .sport-block-show .sport-block-slider-prev {
                width: 38px !important;
                height: 38px;
                margin-top: -19px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/swiper.js') }}?v=9.4.1"></script>
    @if ($canEdit && $uploadAlbum)
        <script src="{{ asset('frontend/js/puupload/plupload.full.min.js') }}?v=3.1.5"></script>
    @endif
    <script>
        (function () {
            const textContainer = $('.text-container');
            const readNext = $('.read_next');
            const textHeight = textContainer.find('p').height() || 0;

            if (textHeight <= textContainer.height()) {
                readNext.hide();
            }

            readNext.on('click', function (event) {
                event.preventDefault();

                const link = $(this);
                const isHidden = link.attr('data-status') === 'hide';

                textContainer.css('height', isHidden ? textHeight : '140px');
                link.text(isHidden ? 'Скрыть' : 'Читать далее...');
                link.attr('data-status', isHidden ? 'show' : 'hide');
            });

            const hasLoop = {{ $photos->count() > 1 ? 'true' : 'false' }};
            new Swiper('.sport-block-show .gallery-top', {
                slidesPerView: 1,
                spaceBetween: 0,
                loop: hasLoop,
                autoplay: hasLoop ? {
                    delay: 5000,
                    disableOnInteraction: false
                } : false,
                navigation: hasLoop ? {
                    nextEl: '.sport-block-show .sport-block-slider-next',
                    prevEl: '.sport-block-show .sport-block-slider-prev'
                } : false
            });
        })();
    </script>
    @if ($canEdit && $uploadAlbum)
        <script>
            (function () {
                const allowedPhotoExtensions = 'jpg,jpeg,png,gif';
                const uploadError = document.getElementById('photo-upload-error');
                let uploadFailed = false;

                function showUploadError(message) {
                    if (!uploadError) {
                        return;
                    }

                    uploadError.textContent = message;
                    uploadError.style.display = 'block';
                }

                function hideUploadError() {
                    if (!uploadError) {
                        return;
                    }

                    uploadError.textContent = '';
                    uploadError.style.display = 'none';
                }

                function ajaxErrorMessage(response) {
                    if (!response) {
                        return null;
                    }

                    try {
                        const payload = JSON.parse(response);

                        if (payload.error) {
                            return payload.error;
                        }

                        if (payload.message) {
                            return payload.message;
                        }
                    } catch (error) {
                        return null;
                    }

                    return null;
                }

                const uploader = new plupload.Uploader({
                    runtimes: 'html5,flash,silverlight,html4',
                    browse_button: 'pickfiles',
                    container: document.getElementById('container'),
                    url: '{{ route('front.ajax.handle', ['action' => 'add_photo_ajax']) }}',
                    flash_swf_url: '{{ asset('frontend/js/puupload/Moxie.swf') }}',
                    silverlight_xap_url: '{{ asset('frontend/js/puupload/Moxie.xap') }}',
                    filters: {
                        max_file_size: '10mb',
                        mime_types: [
                            {title: 'Изображения', extensions: allowedPhotoExtensions}
                        ]
                    },
                    multipart_params: {
                        _token: '{{ csrf_token() }}',
                        categorie: '{{ $uploadAlbum->id }}',
                        photoalbumable_type: '{{ $sectionType }}',
                        description: ''
                    },
                    init: {
                        PostInit: function () {
                            document.getElementById('filelist').innerHTML = '';
                            document.getElementById('uploadfiles').onclick = function () {
                                uploadFailed = false;
                                hideUploadError();
                                uploader.start();
                                return false;
                            };
                        },
                        BeforeUpload: function (up, file) {
                            const description = $('#' + file.id).find('textarea').val();
                            uploader.settings.multipart_params.description = description || '';
                        },
                        FilesAdded: function (up, files) {
                            hideUploadError();
                            plupload.each(files, function (file) {
                                const reader = new FileReader();

                                reader.onload = function (event) {
                                    document.getElementById('filelist').innerHTML += '<div id="' + file.id + '"><div class="attach big"><img src="' + event.target.result + '" alt=""><b></b><span class="icons-hid"><i class="no_attach" data-tooltip="Не добавлять" data-num="' + file.id + '"><img src="{{ asset('frontend/images/icon-krest.png') }}" alt=""></i></span></div><textarea class="form-control comment_attach" placeholder="Комментарий к фото"></textarea></div><div style="clear:both"></div>';
                                };

                                reader.readAsDataURL(file.getNative());
                            });
                        },
                        UploadProgress: function (up, file) {
                            const item = document.getElementById(file.id);

                            if (item) {
                                item.getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + '%</span>';
                            }
                        },
                        FileUploaded: function (up, file, response) {
                            const message = ajaxErrorMessage(response.response);

                            if (message) {
                                uploadFailed = true;
                                showUploadError(message);
                            }
                        },
                        Error: function (up, error) {
                            uploadFailed = true;

                            if (error.code === plupload.FILE_EXTENSION_ERROR) {
                                showUploadError('Можно загружать только изображения: JPG, PNG или GIF.');
                                return;
                            }

                            if (error.code === plupload.FILE_SIZE_ERROR) {
                                showUploadError('Фото не загружено: размер файла больше 10 МБ.');
                                return;
                            }

                            showUploadError(
                                ajaxErrorMessage(error.response)
                                    || error.message
                                    || 'Не удалось загрузить фото.'
                            );
                        }
                    }
                });

                uploader.init();
                uploader.bind('UploadComplete', function () {
                    if (!uploadFailed) {
                        location.reload();
                    }
                });

                $(document).on('click', '.no_attach', function () {
                    const num = $(this).attr('data-num');
                    $('div[id=' + num + ']').remove();
                    uploader.removeFile(num);
                });
            })();
        </script>
    @endif
@endpush
