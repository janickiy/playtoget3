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
                    <input type="hidden" name="id_place" class="id_place" data-type="search_city">
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
            @forelse ($items as $item)
                @include('front.sport-blocks._card', [
                    'item' => $item,
                    'routePrefix' => $routePrefix,
                    'viewer' => $viewer ?? null,
                ])
            @empty
                <center><h5>Записей пока нет.</h5></center>
            @endforelse
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
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            $('.sport-blocks-search-form .lupa span').on('click', function () {
                $(this).closest('form').trigger('submit');
            });
        })();
    </script>
@endpush
