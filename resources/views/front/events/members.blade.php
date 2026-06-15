@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @include('front.events._top')

        @if ($communityAccessDenied ?? false)
            @include('front.communities._closed-message', ['message' => $communityAccessMessage])
        @else
            <div class="photo-caption">
                <h3>Участники<sup>{{ $members->count() }}</sup></h3>
            </div>

            @if ($members->isNotEmpty())
                <div class="possible-friend">
                    @foreach ($members as $member)
                        <div class="col-xs-6 possible-friend-cart">
                            <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                                <img src="{{ $member['avatar'] }}" alt="">
                            </a>
                            <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}">
                                <h5><strong>{{ $member['firstname'] }}<span class="status_user{{ $member['is_online'] ? ' online' : '' }}" data-num="{{ $member['id'] }}"></span><br>{{ $member['lastname'] }}</strong></h5>
                            </a>
                            <p>{{ $member['city'] }}</p>
                            <p>{{ $member['role_name'] }}</p>
                            @if ($viewer)
                                <a href="{{ route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $member['id']]) }}"><b></b></a><br>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="no_message">Участников пока нет.</p>
            @endif

            @if ($teams->isNotEmpty())
                <div class="photo-caption">
                    <h3>Команды<sup>{{ $teams->count() }}</sup></h3>
                </div>
                <div class="event-container">
                    @foreach ($teams as $team)
                        <div class="event-item">
                            <a class="img" href="{{ $team['url'] }}"><img src="{{ $team['avatar'] }}" alt="" class="marginLeft-100"></a>
                            <div class="teg">
                                <p><a href="{{ $team['url'] }}">{{ $team['name'] }}</a></p>
                                <p>{{ $team['sport_type'] }}<br>{{ $team['place'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($groups->isNotEmpty())
                <div class="photo-caption">
                    <h3>Группы<sup>{{ $groups->count() }}</sup></h3>
                </div>
                <div class="event-container">
                    @foreach ($groups as $group)
                        <div class="event-item">
                            <a class="img" href="{{ $group['url'] }}"><img src="{{ $group['avatar'] }}" alt="" class="marginLeft-100"></a>
                            <div class="teg">
                                <p><a href="{{ $group['url'] }}">{{ $group['name'] }}</a></p>
                                <p>{{ $group['sport_type'] }}<br>{{ $group['place'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($applications->isNotEmpty())
                <div class="photo-caption">
                    <h3>Заявки</h3>
                </div>
                <div class="possible-friend">
                    @foreach ($applications as $member)
                        <div class="col-xs-6 possible-friend-cart">
                            <a class="possible-avatar" href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><img src="{{ $member['avatar'] }}" alt=""></a>
                            <a href="{{ route('front.profile.show', ['user' => $member['id']]) }}"><h5><strong>{{ $member['name'] }}</strong></h5></a>
                            <p>{{ $member['city'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
