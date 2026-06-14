@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends sport-block-show">
        <div class="container_in_swiper">
            <div class="swiper-container gallery-top">
                <div class="swiper-wrapper">
                    @forelse ($photos as $photo)
                        <div class="swiper-slide" style="background-image:url('{{ $photo['big'] ?: asset('frontend/images/default_group.png') }}')"></div>
                    @empty
                        <div class="swiper-slide" style="background-image:url('{{ asset('frontend/images/default_group.png') }}')"></div>
                    @endforelse
                </div>
                <div class="swiper-button-next swiper-button-white"></div>
                <div class="swiper-button-prev swiper-button-white"></div>
            </div>
            <div class="swiper-container gallery-thumbs">
                <div class="swiper-wrapper left220">
                    @forelse ($photos as $photo)
                        <div class="swiper-slide" style="background-image:url('{{ $photo['small'] ?: $photo['big'] ?: asset('frontend/images/default_group.png') }}')"></div>
                    @empty
                        <div class="swiper-slide" style="background-image:url('{{ asset('frontend/images/default_group.png') }}')"></div>
                    @endforelse
                </div>
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
                            <button id="pickfiles" type="button" class="save-button">Добавить файлы</button>
                            <button id="uploadfiles" type="button" class="save-button">Загрузить файлы</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if ($photos->isNotEmpty())
            <br><br>
            <div class="photo-container pop-photos">
                @foreach ($photos as $photo)
                    @if ($photo['small'])
                        <div class="hov" id="photo-block-{{ $photo['id'] }}">
                            <a class="photo_big" title="{{ $photo['description'] }}" href="{{ $photo['big'] }}" data-lightbox="roadtrip" data-num="{{ $photo['id'] }}">
                                <img src="{{ $photo['small'] }}" alt="">
                                <div class="transparent"></div>
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/swiper.min.css') }}?v=6.8.4">
    <style>
        .sport-block-show #filelist {
            margin-top: 20px;
        }

        .sport-block-show .container_in_swiper {
            margin-top: 0;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/swiper.js') }}?v=6.8.4"></script>
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
            const galleryThumbs = new Swiper('.gallery-thumbs', {
                spaceBetween: 10,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true,
                touchRatio: 0.2,
                loop: hasLoop,
                loopedSlides: 5,
                slideToClickedSlide: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false
                }
            });
            const galleryTop = new Swiper('.gallery-top', {
                spaceBetween: 10,
                loop: hasLoop,
                loopedSlides: 5,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev'
                },
                thumbs: {
                    swiper: galleryThumbs
                }
            });
        })();
    </script>
    @if ($canEdit && $uploadAlbum)
        <script>
            (function () {
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
                            {title: 'Image files', extensions: 'jpg,gif,png,jpeg'}
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
                                uploader.start();
                                return false;
                            };
                        },
                        BeforeUpload: function (up, file) {
                            const description = $('#' + file.id).find('textarea').val();
                            uploader.settings.multipart_params.description = description || '';
                        },
                        FilesAdded: function (up, files) {
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
                        }
                    }
                });

                uploader.init();
                uploader.bind('UploadComplete', function () {
                    location.reload();
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
