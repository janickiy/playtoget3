@extends('front.layouts.app')

@section('content')
    <a href="{{ route('front.profile.show', ['user' => $receiver->id]) }}">
        {{ $receiver->firstname ?: $receiver->displayName() }} {{ $receiver->firstname ? $receiver->lastname : '' }}
    </a>
    <a href="{{ route('front.profile.messages.index', ['user' => $viewer->id]) }}" class="float_right">К списку диалогов</a>

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
            <h5 class="no_message">Здесь будет история переписки</h5>
        @endforelse

        <div id="message-list" data-num="{{ $receiver->id }}"></div>
        <div id="addMessageContainer"></div>

        <div class="typing">
            <div class="animate">
                <img src="{{ asset('frontend/images/icon-news-pen-active.png') }}" alt="">
            </div>
            <span>Набирает сообщение</span>
            <span class="dotten"></span>
        </div>
    </div>

    @if ($canSendMessage)
        <div class="wrap_text_dialog">
            <div class="ava_dialog">
                <a href="{{ route('front.profile.show', ['user' => $viewer->id]) }}">
                    <img src="{{ \App\Helpers\FrontAssets::userAvatar($viewer) }}" alt="">
                </a>
            </div>
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
                            <a href="#" class="files" data-num="0" data-tooltip="Прикрепить изображение">
                                <img src="{{ asset('frontend/images/files.png') }}" alt="">
                            </a>
                            <div class="smilesChoose" data-num="0"></div>
                        </div>
                    </div>
                    <div class="control static_control">
                        <div class="smilesChoose static_smile block_smile"></div>
                        <input class="btn btn-success" id="submit" type="submit" value="Отправить">
                    </div>
                    <div class="files_block" data-num="0"></div>
                </form>
            </div>
            <div class="ava_dialog">
                <a href="{{ route('front.profile.show', ['user' => $receiver->id]) }}">
                    <img src="{{ \App\Helpers\FrontAssets::userAvatar($receiver) }}" alt="">
                </a>
            </div>
        </div>
    @else
        <center><h4>Вы не можете написать сообщение пользователю</h4></center>
    @endif
@endsection

@push('scripts')
    <script>
        window.profileAjaxBase = '{{ url('/ajax') }}';
        window.dialoguesBase = '{{ url('/profile/' . $viewer->id . '/messages/user') }}';
        window.profileBase = '{{ url('/profile') }}';
        window.dialogueMessagesHasMore = {{ $hasMoreMessages ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('frontend/js/profile.js') }}"></script>
@endpush
