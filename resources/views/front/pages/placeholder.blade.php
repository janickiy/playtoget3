@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>{{ $title ?? 'Section' }}</h3>
    </div>

    @isset($entity)
        @if ($entity)
            <div class="news-block-item">
                <div class="news-block-head">
                    <p class="head-topic">{{ $entity->name ?? $entity->title ?? $entity->displayName() ?? 'Record' }}</p>
                    <div class="clearfix"></div>
                </div>
                <div class="news-block-content">
                    <div class="article nov">{!! $entity->about ?? $entity->description ?? $entity->text ?? '' !!}</div>
                </div>
            </div>
        @else
            <p>Record not found.</p>
        @endif
    @endisset

    @isset($entityId)
        <p>ID: {{ $entityId }}</p>
    @endisset

    @isset($childId)
        <p>Related record: {{ $childId }}</p>
    @endisset

    @isset($items)
        @forelse ($items as $item)
            <div class="news-block-item">
                <div class="news-block-head">
                    <p class="head-topic">{{ $item->name ?? $item->title ?? 'Record' }}</p>
                    <div class="clearfix"></div>
                </div>
                <div class="news-block-content">
                    <div class="article nov">{!! $item->about ?? $item->description ?? '' !!}</div>
                </div>
            </div>
        @empty
            <p>No records yet.</p>
        @endforelse
    @endisset
@endsection
