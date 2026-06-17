@if ($comment['parent_id'] > 0)
    <div class="message-reply message" id="message-{{ $comment['id'] }}" data-item="{{ $comment['parent_id'] }}">
        <div class="message">
            @if ($comment['can_delete'])
                <div class="del_mess" data-item="{{ $comment['id'] }}"></div>
            @endif
            <div class="message-account">
                <img src="{{ $comment['avatar'] }}" alt="" class="img-account">
                <h5 class="name">
                    <a href="{{ $comment['author_url'] }}">
                        {{ $comment['author_name'] }}
                        <span class="status_user" data-num="{{ $comment['author_id'] }}"></span>
                    </a>
                </h5>
                <p class="data">{{ $comment['created'] }}</p>
            </div>
            <p class="message-reply-text">
                {!! nl2br(e($comment['content'])) !!}<br>
                <ul class="attach_image">
                    @foreach ($comment['attachments'] as $attachment)
                        <li><img src="{{ $attachment['url'] }}" class="photo_big" data-num="{{ $attachment['photo_id'] }}" alt=""></li>
                    @endforeach
                </ul>
            </p>
        </div>
    </div>
@else
    <div id="message-{{ $comment['id'] }}" data-item="{{ $comment['parent_id'] }}" class="message">
        <div class="message-account">
            <img src="{{ $comment['avatar'] }}" alt="" class="img-account">
            <h5 class="name">
                <a href="{{ $comment['author_url'] }}">
                    {{ $comment['author_name'] }}
                    <span class="status_user" data-num="{{ $comment['author_id'] }}"></span>
                </a>
            </h5>
            <p class="data">{{ $comment['created'] }}</p>
        </div>

        @if ($comment['can_delete'])
            <div class="del_mess" data-item="{{ $comment['id'] }}"></div>
        @endif

        <p class="message-text">
            {!! nl2br(e($comment['content'])) !!}<br>
            <ul class="attach_image">
                @foreach ($comment['attachments'] as $attachment)
                    <li><img src="{{ $attachment['url'] }}" class="photo_big" data-num="{{ $attachment['photo_id'] }}" alt=""></li>
                @endforeach
            </ul>
        </p>

        @if ($comment['can_interact'] ?? (bool) $viewer)
            <a id="reply-{{ $comment['id'] }}" class="reply" data-item="{{ $comment['id'] }}"> Reply</a>
            @if ($comment['can_share'])
                <a id="tell-comment-{{ $comment['id'] }}" class="tell" data-item="{{ $comment['id'] }}" data-type="comment">{{ $comment['shares_count'] }}</a>
            @endif
            <a id="like-comment-{{ $comment['id'] }}" class="liked" data-item="{{ $comment['id'] }}" data-type="comment">{{ $comment['likes_count'] }}</a>
        @endif
    </div>

    @foreach ($comment['replies'] as $reply)
        @include('front.profile._comment', ['comment' => $reply, 'viewer' => $viewer])
    @endforeach
@endif
