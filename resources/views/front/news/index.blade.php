@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>{{ $title }}</h3>
    </div>

    <div id="comment-list">
        @forelse ($news as $item)
            <div class="news-block-item">
                <div class="news-block-head">
                    <p class="head-topic">{{ $item->title }}</p>
                    @if ($item->created_at)
                        <p class="data">{{ $item->created_at->format('d.m.Y H:i') }}</p>
                    @endif
                    <div class="clearfix"></div>
                </div>
                <div class="news-block-content">
                    <div class="article nov">{!! $item->description !!}</div>
                    @if ($item->link)
                        <a href="{{ $item->link }}" rel="noopener" target="_blank">Источник</a>
                    @endif
                </div>
            </div>
        @empty
            <p>Новостей пока нет.</p>
        @endforelse
    </div>
@endsection
