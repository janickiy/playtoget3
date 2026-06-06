@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        <div class="photo-caption">
            <h3>{{ isset($viewedUserId) ? 'Команды пользователя' : 'Команды' }}</h3>
        </div>

        @empty($viewedUserId)
            <div class="add-photos-album">
                <span><i></i><a href="{{ route('front.teams.create') }}">Создать команду</a></span>
            </div>
        @endempty

        @if ($popularTeams->isNotEmpty())
            <div class="photo-caption">
                <h3>Популярные команды<sup>{{ $popularTeams->count() }}</sup></h3>
            </div>
            <div class="event-container">
                @foreach ($popularTeams as $team)
                    @include('front.teams._team-card', ['team' => $team])
                @endforeach
            </div>
        @endif

        <div class="photo-caption">
            <h3>{{ isset($viewedUserId) ? 'Команды' : 'Мои команды' }}<sup>{{ $myTeams->count() }}</sup></h3>
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

        @empty($viewedUserId)
            <div class="photo-caption">
                <h3>Все команды<sup>{{ $teams->count() }}</sup></h3>
            </div>
            @if ($teams->isNotEmpty())
                <div class="event-container">
                    @foreach ($teams as $team)
                        @include('front.teams._team-card', ['team' => $team])
                    @endforeach
                </div>
            @endif
        @endempty
    </div>
@endsection
