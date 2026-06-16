@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends search-page">
        <form autocomplete="off" action="{{ route('front.home') }}" method="GET" role="search" class="search-page-form">
            <div class="add-photos-album selects-field-events">
                <p class="select-container-text lupa width100">
                    <input type="text" name="search" value="{{ $query }}" class="search_word border-top-none padding25" placeholder="Введите ключевое слово">
                    <span class="padding2"></span>
                </p>
                <input type="submit" class="displayNone">
            </div>
        </form>

        <div class="photo-caption front-section-title">
            <h3>Поиск</h3>
        </div>

        @if ($query === '')
            <p class="no_message search-empty-message">Введите ключевое слово для поиска.</p>
        @else
            <p class="search-summary">
                Результаты по запросу «{{ $query }}»: {{ $total }}
            </p>

            @include('front.search._section', [
                'title' => 'Команды',
                'items' => $results['teams'],
                'emptyText' => 'Команды не найдены.',
                'partial' => 'front.teams._team-card',
                'itemName' => 'team',
            ])

            @include('front.search._section', [
                'title' => 'Группы',
                'items' => $results['groups'],
                'emptyText' => 'Группы не найдены.',
                'partial' => 'front.groups._group-card',
                'itemName' => 'group',
            ])

            @include('front.search._section', [
                'title' => 'Мероприятия',
                'items' => $results['events'],
                'emptyText' => 'Мероприятия не найдены.',
                'partial' => 'front.events._event-card',
                'itemName' => 'event',
            ])

            <div class="search-section">
                <div class="photo-caption">
                    <h5 class="center_text">Спортивные блоки</h5>
                </div>

                @if ($results['sportBlocks']->isNotEmpty())
                    <div class="event-container">
                        @foreach ($results['sportBlocks'] as $item)
                            @include('front.sport-blocks._card', [
                                'item' => $item,
                                'routePrefix' => $item['route_prefix'],
                                'viewer' => $frontLayout['user'] ?? null,
                                'editLabel' => 'Редактировать',
                            ])
                        @endforeach
                    </div>
                @else
                    <p class="no_message search-empty-message">Спортивные блоки не найдены.</p>
                @endif
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .search-page .search-page-form {
            margin-bottom: 5px;
        }

        .search-page .search-page-form .width100 {
            width: 100% !important;
        }

        .search-page .search-summary {
            color: #777;
            font-size: 15px;
            margin: -5px 0 22px;
            text-align: center;
        }

        .search-page .search-section {
            clear: both;
            margin-bottom: 28px;
        }

        .search-page .search-section .photo-caption h5 {
            color: #2c2e45;
            font-size: 16px;
            font-weight: 700;
            margin: 18px 0 14px;
            text-transform: uppercase;
        }

        .search-page .search-empty-message {
            margin: 16px 0 26px;
            text-align: center;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            $('.search-page-form .lupa span').on('click', function () {
                $(this).closest('form').trigger('submit');
            });
        });
    </script>
@endpush
