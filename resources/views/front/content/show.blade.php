@extends('front.layouts.app')

@section('content')
    @if ($page)
        <div class="photo-caption">
            <h3>{{ $page->title }}</h3>
        </div>
        <div class="news-block-item">
            <div class="news-block-content">
                <div class="article nov">{!! $page->text !!}</div>
            </div>
        </div>
    @else
        <div class="photo-caption">
            <h3>Страница не найдена</h3>
        </div>
    @endif
@endsection
