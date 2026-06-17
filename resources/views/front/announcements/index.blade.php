@extends('front.layouts.app')

@section('content')
    <div class="photo-caption">
        <h3>Announcements</h3>
    </div>

    <div class="news-block-item">
        <div class="news-block-content">
            @forelse ($announcements as $announcement)
                <div class="article nov">
                    <h4>
                        <a href="{{ route('front.announcements.show', ['slug' => $announcement->slug]) }}">
                            {{ $announcement->title }}
                        </a>
                    </h4>
                    <p>{{ $announcement->created_at?->format('d.m.Y') }}</p>
                    <div>{{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->text), 240) }}</div>
                </div>
            @empty
                <div class="article nov">No announcements yet</div>
            @endforelse
        </div>
    </div>
@endsection
