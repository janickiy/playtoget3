@extends('front.layouts.app')

@section('content')
    @php
        $eventFormCommunityView = $communityView ?? null;
        $eventFormCommunity = $eventFormCommunityView['entity'] ?? ($team ?? $group ?? null);
    @endphp

    <div class="content-groups friends">
        @if ($event)
            @include('front.events._top')
        @elseif ($eventFormCommunityView && $eventFormCommunity)
            @include($eventFormCommunityView['top'])
        @endif

        <div class="photo-caption">
            <h3>{{ $title }}</h3>
        </div>

        @if ($errors->any())
            <div class="mutations-both">
                <p>{{ $errors->first() }}</p>
                <a class="delete">x</a>
            </div>
        @endif

        <div class="job_form">
            <form class="form-horizontal create_form" method="POST" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="education_form">
                    <div class="text-center"><h2>Информация</h2></div>
                    <br>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="name">Название</label>
                        <div class="col-lg-6">
                            <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $event?->name) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="description">Описание</label>
                        <div class="col-lg-6">
                            <textarea class="form-control form-dark" id="description" rows="5" name="description">{{ old('description', $event?->description) }}</textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="place">Город</label>
                        <div class="col-lg-6 event-form-autocomplete">
                            <input type="hidden" name="id_place" value="{{ old('id_place') }}" class="id_place" data-type="search_city">
                            <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('place', $event?->place) }}" name="place" data-type="search_city">
                            <div class="select-place" data-type="search_city"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="address">Адрес</label>
                        <div class="col-lg-6">
                            <input class="form-control" type="text" id="address" name="address" value="{{ old('address', $event?->address) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="sport">Вид спорта</label>
                        <div class="col-lg-6 event-form-autocomplete">
                            <input type="hidden" name="id_sport" class="id_place" value="{{ old('id_sport') }}" data-type="search_sport">
                            <input autocomplete="off" class="form-control search_word text-place border-top-none" type="text" value="{{ old('sport', $event?->sport_type) }}" name="sport" data-type="search_sport">
                            <div class="select-place" data-type="search_sport"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="date_from">Начало</label>
                        <div class="col-lg-6">
                            <input class="form-control" type="datetime-local" id="date_from" name="date_from" value="{{ old('date_from', $event?->date_from?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label" for="date_to">Окончание</label>
                        <div class="col-lg-6">
                            <input class="form-control" type="datetime-local" id="date_to" name="date_to" value="{{ old('date_to', $event?->date_to?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="form-group event-form-cover">
                        <label class="col-lg-3 control-label">Обложка</label>
                        <div class="col-lg-6">
                            <img id="preview_cover" src="{{ $event ? $eventData['cover'] : asset('frontend/images/content-bg.png') }}" alt="">
                            <div class="file_upload team-file-upload">
                                <button type="button">Загрузить обложку</button>
                                <input class="event-cover-input" type="file" name="cover_file" accept="image/jpeg,image/png,image/gif">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group center_text">
                    <input class="btn-form save-button" type="submit" value="{{ $button }}">
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .event-form-cover img {
            display: block;
            width: 320px;
            height: 110px;
            object-fit: cover;
            margin: 0 0 10px;
            border-radius: 5px;
            border: 1px solid #eaebed;
            background: #eef5f5;
        }

        .event-form-autocomplete {
            position: relative;
        }

        .event-form-autocomplete .select-place {
            border-radius: 0 0 5px 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
            left: 0;
            max-height: 240px;
            top: 36px;
            width: 100%;
            z-index: 200;
        }

        .event-form-autocomplete .select-place .place-item {
            font-size: 18px;
            line-height: 30px;
            padding: 6px 10px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/search.js') }}"></script>
    <script>
        if (typeof selectAction === 'function') {
            selectAction();
        }

        (function () {
            const input = document.querySelector('.event-cover-input');
            const image = document.querySelector('#preview_cover');

            if (!input || !image) {
                return;
            }

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];

                if (!file || !file.type.match(/^image\//)) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    image.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
@endpush
