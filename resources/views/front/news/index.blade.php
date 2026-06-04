@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>
            {{ $title }}
            @if (! empty($newNewsCount))
                <sup> +{{ $newNewsCount }}</sup>
            @endif
        </h3>
    </div>

    <div id="comment-list">
        @forelse ($news as $item)
            <div class="news-block-item" data-toggle="modal" data-target="#second-post">
                <div class="news-block-head">
                    <a href="{{ route('front.profile.show', ['user' => $item['author_id']]) }}">
                        <div class="head-img"><img src="{{ $item['avatar'] }}" alt=""></div>
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
        @empty
            <p>Новостей пока нет.</p>
        @endforelse
    </div>
@endsection
