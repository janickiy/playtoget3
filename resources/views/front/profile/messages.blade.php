@extends('front.layouts.app')

@section('content')
    @php
        $receiverName = trim(($receiver->firstname ?: $receiver->displayName()) . ' ' . ($receiver->firstname ? $receiver->lastname : ''));
    @endphp

    <div class="dialogue-header">
        <a class="dialogue-header-avatar" href="{{ route('front.profile.show', ['user' => $receiver->id]) }}">
            <img src="{{ \App\Helpers\FrontAssets::userAvatar($receiver) }}" alt="">
        </a>
        <div class="dialogue-header-main">
            <span class="dialogue-header-label">Dialogue with user</span>
            <h1>
                <a href="{{ route('front.profile.show', ['user' => $receiver->id]) }}">{{ $receiverName }}</a>
            </h1>
        </div>
        <a href="{{ route('front.profile.messages.index', ['user' => $viewer->id]) }}" class="dialogue-back-link">
            <span aria-hidden="true">&larr;</span>
            Back to dialogues
        </a>
    </div>

    <div
        class="mess_list"
        data-endpoint="{{ route('front.ajax.handle', ['action' => 'get_messages']) }}"
        data-number="{{ $messagesPageSize }}"
        data-offset="{{ $messagesPageSize }}"
        data-has-more="{{ $hasMoreMessages ? 1 : 0 }}"
    >
        @forelse ($messages as $message)
            @include('front.profile._dialog-message', ['message' => $message, 'viewer' => $viewer])
        @empty
            <h5 class="no_message">Message history will appear here</h5>
        @endforelse

        <div id="message-list" data-num="{{ $receiver->id }}"></div>
        <div id="addMessageContainer"></div>

        <div class="typing">
            <div class="animate">
                <img src="{{ asset('frontend/images/icon-news-pen-active.png') }}" alt="">
            </div>
            <span>Typing a message</span>
            <span class="dotten"></span>
        </div>
    </div>

    @if ($canSendMessage)
        <div class="wrap_text_dialog dialogue-compose">
            <div class="text_form">
                <form id="addMessageForm" class="form-horizontal" method="POST" action="">
                    @csrf
                    <input type="hidden" name="sender_id" value="{{ $viewer->id }}">
                    <input type="hidden" name="receiver_id" value="{{ $receiver->id }}">
                    <div class="form-group">
                        <div class="col-lg-7 message_textarea">
                            <input type="file" class="file_name" name="file_name[]" data-num="0" multiple>
                            <textarea class="form-control form-dark padding-right" id="message" rows="4" name="message"></textarea>
                        </div>
                        <div class="smile-files">
                            <a id="smilesBtn" class="smile smilesBtn" data-num="0">
                                <img src="{{ asset('frontend/images/smile.png') }}" alt="">
                            </a>
                            <a href="#" class="files" data-num="0" data-tooltip="Attach image">
                                <img src="{{ asset('frontend/images/files.png') }}" alt="">
                            </a>
                            <div class="smilesChoose" data-num="0"></div>
                        </div>
                    </div>
                    <div class="control static_control">
                        <input class="btn btn-success" id="submit" type="submit" value="Send">
                    </div>
                    <div class="files_block" data-num="0"></div>
                </form>
            </div>
        </div>
    @else
        <div class="text-center"><h4>You cannot message this user</h4></div>
    @endif
@endsection

@push('scripts')
    <script>
        window.profileAjaxBase = '{{ url('/ajax') }}';
        window.dialoguesBase = '{{ url('/profile/' . $viewer->id . '/messages/user') }}';
        window.profileBase = '{{ url('/profile') }}';
        window.dialogueMessagesHasMore = {{ $hasMoreMessages ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('frontend/js/profile.js') }}?v=2026061410"></script>
@endpush
