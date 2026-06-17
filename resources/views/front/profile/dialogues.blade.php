@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>Dialogs</h3>
    </div>

    <div class="row dialogues new_dialog" data-status="new">
        <h5><img src="{{ asset('frontend/images/message-sitebar.svg') }}" alt=""> Start a new dialog</h5>
    </div>

    <div class="mess_list">
        <div class="container_dialog hide" id="new_dialogue">
            @forelse ($friends as $friend)
                @php
                    $messageUrl = route('front.profile.messages.show', ['user' => $viewer->id, 'recipient' => $friend->id]);
                    $profileUrl = route('front.profile.show', ['user' => $friend->id]);
                @endphp
                <div class="row dialogues" data-num="{{ $friend->id }}" onclick="window.location.href = '{{ $messageUrl }}'">
                    <div class="col-md-12">
                        <a href="{{ $profileUrl }}">
                            <img src="{{ \App\Helpers\FrontAssets::userAvatar($friend) }}" width="50" alt="" class="img-account float_left">
                            <div class="fromwho">
                                {{ $friend->firstname ?: $friend->displayName() }} {{ $friend->firstname ? $friend->lastname : '' }}
                            </div>
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center"><h4>You have no friends yet</h4></div>
                <div class="text-center"><h5><a href="{{ route('front.friends.index') }}">View possible friends</a></h5></div>
            @endforelse
        </div>

        <div class="container_dialog" id="old_dialogue">
            @forelse ($dialogues as $dialogue)
                <div
                    class="row dialogues"
                    data-num="{{ $dialogue['user_id'] }}"
                    onclick="window.location.href = '{{ $dialogue['message_url'] }}'"
                >
                    <div class="col-md-4">
                        <a href="{{ $dialogue['profile_url'] }}">
                            <img src="{{ $dialogue['avatar'] }}" width="50" alt="" class="img-account float_left">
                            <div class="fromwho name_dialog">
                                {{ $dialogue['firstname'] }}<br>
                                {{ $dialogue['lastname'] }}<br>
                                <span>{{ $dialogue['last_message']['created'] }}</span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-8">
                        <div class="block-dialog">
                            <span class="ahref{{ $dialogue['unread'] ? ' status_red' : '' }}">
                                <img src="{{ $dialogue['last_message']['avatar'] }}" alt="" class="img-mess-dialog">
                                {!! $dialogue['last_message']['content'] !!}
                            </span>
                        </div>
                    </div>
                    <div class="del-dialog" data-item="{{ $dialogue['user_id'] }}" data-tooltip="Clear dialogue"></div>
                </div>
            @empty
                <div class="text-center"><h4 class="no_dialogues">You have no dialogs yet</h4></div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.profileAjaxBase = '{{ url('/ajax') }}';
        window.dialoguesBase = '{{ url('/profile/' . $viewer->id . '/messages/user') }}';
        window.profileBase = '{{ url('/profile') }}';
    </script>
    <script src="{{ asset('frontend/js/profile.js') }}?v=2026061410"></script>
@endpush
