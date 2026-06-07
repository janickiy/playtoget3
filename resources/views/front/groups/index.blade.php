@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        <form autocomplete="off" action="{{ route('front.playgrounds.index') }}" method="GET" role="search">
            <div class="add-photos-album selects-field-events groups-search-form">
                <div class="select-container-text two_block">
                    <input type="hidden" name="id_place" class="id_place" data-type="search_city">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('place') }}" name="place" data-type="search_city" placeholder="Искать группу в городе">
                    <div class="select-place" data-type="search_city"></div>
                </div>
                <div class="select-container-text two_block borderLeft">
                    <input type="hidden" name="id_sport" class="id_place" data-type="search_sport">
                    <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('sport') }}" name="sport" data-type="search_sport" placeholder="Искать вид спорта">
                    <div class="select-place" data-type="search_sport"></div>
                </div>
                <p class="select-container-text lupa">
                    <input type="text" name="search" value="{{ request('search') }}" class="search_word" placeholder="Ключевое слово">
                    <span></span>
                </p>
                <input type="submit" class="displayNone">
                <button type="button" onclick="location.href='{{ route('front.playgrounds.create') }}'" class="btn btn-white">Создать группу</button>
            </div>
        </form>

        <div id="tabs">
            <ul id="main-menu" class="marginBottom-40">
                <li data-type="popular" class="active"><a href="#popular">Популярные группы</a></li>
                <li data-type="mygroups">
                    <a href="#mygroups">Мои группы
                        @if ($myGroups->isNotEmpty())
                            <sup>{{ $myGroups->count() }}</sup>
                        @endif
                    </a>
                </li>
                <li data-type="invited">
                    <a href="#invited">Меня пригласили
                        @if ($invitedGroups->isNotEmpty())
                            <sup class="active">{{ $invitedGroups->count() }}</sup>
                        @endif
                    </a>
                </li>
            </ul>

            <div id="popular" class="paddingTop20">
                @if ($popularGroups->isNotEmpty())
                    <div class="event-container">
                        <div id="pop_group_list">
                            @foreach ($popularGroups as $group)
                                @include('front.groups._group-card', ['group' => $group])
                            @endforeach
                        </div>
                    </div>
                @else
                    <center><h5>Популярные группы отсутствуют</h5></center>
                @endif
            </div>

            <div id="mygroups" class="paddingTop20" style="display:none">
                @if ($myGroups->isNotEmpty())
                    <div class="event-container">
                        @foreach ($myGroups as $group)
                            @include('front.groups._group-card', ['group' => $group])
                        @endforeach
                    </div>
                @else
                    <center><h5>Вы пока не вступали в группы</h5></center>
                @endif
            </div>

            <div id="invited" class="paddingTop20" style="display:none">
                @if ($invitedGroups->isNotEmpty())
                    <div class="event-container">
                        @foreach ($invitedGroups as $group)
                            @include('front.groups._group-card', ['group' => $group])
                        @endforeach
                    </div>
                @else
                    <center><h5>У вас нет приглашений.</h5></center>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
    <style>
        .content-groups .event-container .event-item p.group-members {
            color: #929292;
            font-size: 12px;
            font-weight: 100;
            margin-bottom: 4px;
        }

        .content-groups .event-container .event-item p.group-members i {
            background: url('{{ asset('frontend/images/icon-running.png') }}') no-repeat;
            display: inline-block;
            height: 14px;
            margin-right: 5px;
            vertical-align: -2px;
            width: 18px;
        }

        .content-groups .groups-search-form:after {
            clear: both;
            content: "";
            display: block;
        }

        .content-groups #tabs {
            clear: both;
            margin-top: 10px;
        }

        .content-groups #tabs #main-menu.marginBottom-40 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 0 0 20px !important;
            padding: 0;
        }

        .content-groups #tabs #main-menu li {
            box-sizing: border-box;
            float: none;
            font-size: 13px;
            font-weight: 400;
            line-height: 40px;
            list-style: none;
            margin: 0;
            min-width: 0;
            padding: 0 !important;
            position: relative;
            text-align: center !important;
            width: 100% !important;
        }

        .content-groups #tabs #main-menu li a {
            box-sizing: border-box;
            display: block;
            height: 40px;
            line-height: 40px;
            padding: 0;
            text-align: center;
            white-space: nowrap;
            width: 100%;
        }

        .content-groups #tabs #main-menu li sup {
            border-radius: 50px;
            display: block;
            height: 15px;
            line-height: 15px;
            position: absolute;
            right: 5px;
            text-align: center;
            top: 5px;
            width: 15px;
        }

        .content-groups #tabs > .paddingTop20 {
            clear: both;
            padding-top: 0 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const $tabs = $('#tabs');

            if (!$tabs.length) {
                return;
            }

            function activateTab(selector) {
                $tabs.children('div').hide();
                $(selector).show();
                $tabs.find('#main-menu li').removeClass('active ui-state-active');
                $tabs.find('#main-menu a[href="' + selector + '"]').closest('li').addClass('active ui-state-active');
            }

            $tabs.find('#main-menu a').on('click', function (event) {
                event.preventDefault();
                activateTab($(this).attr('href'));
            });

            $('.groups-search-form .lupa span').on('click', function () {
                $(this).closest('form').trigger('submit');
            });

            activateTab('#popular');
        })();
    </script>
@endpush
