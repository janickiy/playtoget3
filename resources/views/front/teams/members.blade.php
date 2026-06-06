@extends('front.layouts.app')

@section('content')
    <div class="content-groups friends">
        @include('front.teams._top')

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
    </div>
@endsection
