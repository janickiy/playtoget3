@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends sport-blocks-page">
        <form autocomplete="off" action="{{ $indexRoute }}" method="GET" role="search">
            <div class="add-photos-album selects-field-events sport-blocks-search-form">
                <p class="select-container-text lupa width100">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="search_word border-top-none padding25" placeholder="Ключевое слово">
                    <span class="padding2"></span>
                </p>
                <div class="select-container-text two_block">
                    <input type="hidden" name="id_place" class="id_place" value="{{ $filters['id_place'] ?? '' }}" data-type="search_city">
                    <input autocomplete="off" class="search_word text-place" type="text" name="place" value="{{ $filters['place'] ?? '' }}" data-type="search_city" placeholder="{{ $searchPlaceholder }}">
                    <div class="select-place" data-type="search_city"></div>
                </div>
                <input type="submit" class="displayNone">
                <button type="button" onclick="location.href='{{ $createRoute }}'" class="btn btn-white">{{ $createButton }}</button>
            </div>
        </form>

        <div class="clearfix"></div>
        <div class="photo-caption">
            <h3>{{ $listTitle }}</h3>
        </div>

        <div class="event-container">
            <div id="sport_block_list"
                 data-next-offset="{{ $items->count() }}"
                 data-page-size="{{ $itemsPageSize ?? 5 }}"
                 data-has-more="{{ ($itemsTotal ?? $items->count()) > $items->count() ? 1 : 0 }}">
                @include('front.sport-blocks._cards', [
                    'items' => $items,
                    'routePrefix' => $routePrefix,
                    'viewer' => $viewer ?? null,
                    'editLabel' => $editLabel ?? 'Редактировать',
                ])
            </div>
            @if ($items->isEmpty())
                <div class="text-center"><h5>Записей пока нет.</h5></div>
            @endif
            <a href="#" class="show-more js-sport-blocks-load-more" @style(['display: none' => ($itemsTotal ?? $items->count()) <= $items->count()])>
                <i></i><span>Показать еще</span>
            </a>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
    <style>
        .sport-blocks-page .sport-blocks-search-form {
            overflow: visible;
        }

        .sport-blocks-page .sport-blocks-search-form:after {
            clear: both;
            content: "";
            display: block;
        }

        .sport-blocks-page .sport-blocks-search-form .width100 {
            width: 100% !important;
        }

        .sport-blocks-page .sport-blocks-search-form .btn-white {
            min-height: 38px;
            text-transform: uppercase;
        }

        .sport-blocks-page .event-container {
            clear: both;
        }

        .sport-blocks-page .show-more.sport-blocks-loading {
            opacity: .6;
            pointer-events: none;
        }

        .sport-blocks-page .sport-blocks-search-form .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            max-height: 260px;
            top: 100%;
            z-index: 100;
        }

        .sport-blocks-page .sport-blocks-search-form .select-place .place-item {
            font-size: 18px;
            line-height: 30px;
            padding: 6px 10px;
            text-align: center;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/search.js') }}"></script>
    <script>
        (function () {
            const $container = $('#sport_block_list');
            const $button = $('.js-sport-blocks-load-more');
            let loading = false;
            let scrollTimer = null;

            function searchPayload() {
                const payload = {};
                const params = new URLSearchParams(window.location.search);

                ['id_place', 'place', 'search'].forEach(function (name) {
                    payload[name] = params.get(name) || '';
                });

                return payload;
            }

            function loadSportBlocks() {
                if (!$container.length || loading || Number($container.data('has-more')) !== 1) {
                    return;
                }

                const pageSize = Number($container.data('page-size')) || 5;
                const offset = Number($container.data('next-offset')) || 0;

                loading = true;
                $button.addClass('sport-blocks-loading');

                $.ajax({
                    type: 'POST',
                    url: '{{ route('front.ajax.handle', ['action' => 'get_sport_blocks_list']) }}',
                    data: Object.assign({
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        number: pageSize,
                        offset: offset,
                        type: '{{ $sectionType }}',
                    }, searchPayload()),
                    success: function (data) {
                        if (data.status === 1 && data.html) {
                            $container.append(data.html);
                            $container.data('next-offset', offset + (Number(data.count) || pageSize));
                            $container.data('has-more', data.has_more ? 1 : 0);
                        } else {
                            $container.data('has-more', 0);
                        }

                        if (Number($container.data('has-more')) !== 1) {
                            $button.hide();
                        }
                    },
                    complete: function () {
                        loading = false;
                        $button.removeClass('sport-blocks-loading');
                        requestNextPageIfNeeded();
                    },
                });
            }

            function requestNextPageIfNeeded() {
                window.clearTimeout(scrollTimer);
                scrollTimer = window.setTimeout(function () {
                    if ($(window).scrollTop() + $(window).height() + 300 >= $(document).height()) {
                        loadSportBlocks();
                    }
                }, 80);
            }

            $('.sport-blocks-search-form .lupa span').on('click', function () {
                $(this).closest('form').trigger('submit');
            });

            $button.on('click', function (event) {
                event.preventDefault();
                loadSportBlocks();
            });

            $(window).on('scroll.sport-blocks', requestNextPageIfNeeded);
            requestNextPageIfNeeded();
        })();
    </script>
@endpush
