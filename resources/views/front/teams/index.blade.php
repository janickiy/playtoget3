@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @empty($viewedUserId)
            <form autocomplete="off" action="{{ route('front.teams.index') }}" method="GET" role="search">
                <div class="add-photos-album selects-field-events">
                    <div class="select-container-text two_block">
                        <input type="hidden" name="id_place" class="id_place" data-type="search_city">
                        <input autocomplete="off" class="search_word text-place border-top-none" type="text" value="{{ request('place') }}" name="place" data-type="search_city" placeholder="Искать команду в городе">
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
                    <button type="button" onclick="location.href='{{ route('front.teams.create') }}'" class="btn btn-white">Создать команду</button>
                </div>
            </form>

            <div id="tabs">
                <ul id="main-menu" class="marginBottom-40">
                    <li data-type="popular" class="active"><a href="#popular">Популярные команды</a></li>
                    <li data-type="mygroups">
                        <a href="#mygroups">Мои команды
                            @if ($myTeams->isNotEmpty())
                                <sup>{{ $myTeams->count() }}</sup>
                            @endif
                        </a>
                    </li>
                    <li data-type="invited">
                        <a href="#invited">Меня пригласили
                            @if ($invitedTeams->isNotEmpty())
                                <sup class="active">{{ $invitedTeams->count() }}</sup>
                            @endif
                        </a>
                    </li>
                </ul>

                <div id="popular" class="paddingTop20">
                    @if ($popularTeams->isNotEmpty())
                        <div class="event-container">
                            <div id="pop_group_list">
                                @foreach ($popularTeams as $team)
                                    @include('front.teams._team-card', ['team' => $team])
                                @endforeach
                            </div>
                        </div>
                    @else
                        <center><h5>Популярных команд пока нет.</h5></center>
                    @endif
                </div>

                <div id="mygroups" class="paddingTop20" style="display:none">
                    @if ($myTeams->isNotEmpty())
                        <div class="event-container">
                            @foreach ($myTeams as $team)
                                @include('front.teams._team-card', ['team' => $team])
                            @endforeach
                        </div>
                    @else
                        <center><h5>Вы еще не вступили ни в одну команду.</h5></center>
                    @endif
                </div>

                <div id="invited" class="paddingTop20" style="display:none">
                    @if ($invitedTeams->isNotEmpty())
                        <div class="event-container">
                            @foreach ($invitedTeams as $team)
                                @include('front.teams._team-card', ['team' => $team])
                            @endforeach
                        </div>
                    @else
                        <center><h5>У вас нет приглашений.</h5></center>
                    @endif
                </div>
            </div>
        @else
            <div class="photo-caption">
                <h3>Команды<sup>{{ $myTeams->count() }}</sup></h3>
            </div>

            @if ($myTeams->isNotEmpty())
                <div class="event-container">
                    @foreach ($myTeams as $team)
                        @include('front.teams._team-card', ['team' => $team])
                    @endforeach
                </div>
            @else
                <p class="no_message">Команд пока нет.</p>
            @endif
        @endempty
    </div>
@endsection

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

            activateTab('#popular');
        })();
    </script>
@endpush
