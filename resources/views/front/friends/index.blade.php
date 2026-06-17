@extends('front.layouts.app')

@section('content')
    <div
        class="friends content-groups"
        data-ajax-base="{{ url('/ajax') }}"
        data-csrf="{{ csrf_token() }}"
        data-profile-base="{{ url('/profile') }}"
        data-message-base="{{ $viewer ? url('/profile/' . $viewer->id . '/messages/user') : url('/profile/' . $targetUser->id) }}"
        data-icon-add="{{ asset('frontend/images/icon-plus.svg') }}"
        data-icon-ok="{{ asset('frontend/images/icon-ok.svg') }}"
        data-icon-remove="{{ asset('frontend/images/icon-krest.svg') }}"
    >
        @if (! $canViewFriends)
            <p class="no_message">The user's friends list is available only to friends.</p>
        @endif

        @if ($isOwnPage && $possibleFriends->isNotEmpty())
            <link rel="stylesheet" type="text/css" href="{{ asset('frontend/css/select2.css') }}">
            <form autocomplete="off" action="{{ $searchRoute }}" method="GET" role="search" class="search_friends">
                <input type="hidden" name="task" value="search">
                <input type="hidden" value="user" name="q">
                <div class="add-photos-album selects-field-events">
                    <div class="select-container-text border-top-none">
                        <input
                            type="text"
                            placeholder="Name"
                            name="search"
                            value="{{ $filters['search'] }}"
                            class="search_word text-place border-top-none border-right-none"
                        >
                    </div>
                    <div class="select-container-text two_block borderLeft">
                        <div class="styled-select styled-select-4">
                            <select name="sex">
                                <option value="">Gender</option>
                                <option value="male" @selected($filters['sex'] === 'male')>Male</option>
                                <option value="female" @selected($filters['sex'] === 'female')>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="select-container-text">
                        <input type="hidden" name="id_place" class="id_place" data-type="search_city" value="{{ request('id_place') }}">
                        <input
                            autocomplete="off"
                            class="search_word text-place"
                            type="text"
                            name="place"
                            value="{{ $filters['city'] }}"
                            data-type="search_city"
                            placeholder="City"
                        >
                        <div class="select-place" data-type="search_city"></div>
                    </div>
                    <div class="select-container-text two_block borderLeft">
                        <input type="hidden" name="id_sport" class="id_place" data-type="search_sport" value="{{ request('id_sport') }}">
                        <input
                            autocomplete="off"
                            class="search_word text-place"
                            type="text"
                            name="sport"
                            value="{{ $filters['sport'] }}"
                            data-type="search_sport"
                            placeholder="Sport type"
                        >
                        <div class="select-place" data-type="search_sport"></div>
                    </div>
                    <div class="select-container-text">
                        <input
                            type="text"
                            placeholder="Age from"
                            maxlength="2"
                            name="min_age"
                            value="{{ $filters['min_age'] }}"
                            class="search_word text-place age border-right-none"
                        >
                        <input
                            type="text"
                            placeholder="Age to"
                            maxlength="2"
                            name="max_age"
                            value="{{ $filters['max_age'] }}"
                            class="search_word text-place age border-right-none borderLeft"
                        >
                    </div>
                    <div class="select-container-text borderLeft borderTop">
                        <div class="checkbox">
                            <input id="checkbox-find-comand" type="checkbox" hidden @checked((string) request('photo', '1') === '1') name="photo" value="1">
                            <label for="checkbox-find-comand">photo</label>
                        </div>
                        <button type="submit" class="btn btn-white">Search</button>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </form>
            <script>selectAction();</script>
            <script src="{{ asset('frontend/js/search.js') }}"></script>

            <div class="photo-caption">
                <h3>Possible friends
                </h3>
            </div>
            <div id="possible-friend" class="possible-friend">
                @foreach ($possibleFriends as $friend)
                    @include('front.friends._card', [
                        'friend' => $friend,
                        'viewer' => $viewer,
                        'action' => 'add',
                        'showMessage' => false,
                    ])
                @endforeach
            </div>
            <a id="show-possible_friends" class="show-more"><i></i>show more</a>
        @endif

        @if ($friends->isNotEmpty())
            <div class="photo-caption">
                <h3>
                    {{ $isOwnPage ? 'my friends' : 'friends' }}
                    <sup>{{ $friendsCount }}</sup>
                </h3>
            </div>
            <div id="friends" class="possible-friend my-friend">
                @foreach ($friends as $friend)
                    @include('front.friends._card', [
                        'friend' => $friend,
                        'viewer' => $viewer,
                        'action' => $isOwnPage ? 'remove' : null,
                    ])
                @endforeach
            </div>
            @if ($hasMoreFriends)
                <a id="show_more_friends" class="show-more" onclick="showMoreFriend({{ $targetUser->id }})"><i></i>show more</a>
            @endif
        @endif

        @if ($isOwnPage && $incomingRequests->isNotEmpty())
            <div class="photo-caption">
                <h3>
                    Friend requests
                    <sup>{{ $incomingRequestsCount }}</sup>
                </h3>
            </div>
            <div class="possible-friend my-friend">
                @foreach ($incomingRequests as $friend)
                    @include('front.friends._card', [
                        'friend' => $friend,
                        'viewer' => $viewer,
                        'action' => 'accept',
                    ])
                @endforeach
            </div>
        @endif

        @if ($isOwnPage && $outgoingRequests->isNotEmpty())
            <div class="photo-caption">
                <h3>
                    Outgoing requests
                    <sup>{{ $outgoingRequestsCount }}</sup>
                </h3>
            </div>
            <div class="possible-friend my-friend">
                @foreach ($outgoingRequests as $friend)
                    @include('front.friends._card', [
                        'friend' => $friend,
                        'viewer' => $viewer,
                    ])
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('frontend/js/friends.js') }}"></script>
@endpush
