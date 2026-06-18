<div class="news-block-item" data-news-key="{{ $item['event_key'] ?? $item['likeable_type'] . ':' . $item['content_id'] }}" data-toggle="modal" data-target="#second-post">
    <div class="news-block-head">
        <a href="{{ route('front.profile.show', ['user' => $item['author_id']]) }}">
            <div class="head-img avatar-status-holder">
                <img src="{{ $item['avatar'] }}" alt="">
                @include('front.partials.user-online-status', [
                    'isOnline' => $item['online'],
                    'userId' => $item['author_id'],
                ])
            </div>
        </a>
        <a href="{{ route('front.profile.show', ['user' => $item['author_id']]) }}">
            <p class="head-topic">
                {{ $item['author_name'] }}
                <span class="status_user{{ $item['online'] ? ' online' : '' }}" data-num="{{ $item['author_id'] }}"></span>
            </p>
        </a>
        <p class="data">{{ $item['date'] }}</p>
        <div class="clearfix"></div>
    </div>
    <div class="news-block-content">
        <div class="article nov">
            {!! $item['message'] !!}
        </div>
        @if ($item['likeable_type'])
            <a class="tell" data-item="{{ $item['content_id'] }}" data-type="{{ $item['likeable_type'] }}">{{ $item['tells_count'] }}</a>
            <a class="liked" data-item="{{ $item['content_id'] }}" data-type="{{ $item['likeable_type'] }}">{{ $item['likes_count'] }}</a>
        @endif
    </div>
</div>
