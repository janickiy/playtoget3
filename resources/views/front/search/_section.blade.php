<div class="search-section">
    <div class="photo-caption">
        <h5 class="center_text">{{ $title }}</h5>
    </div>

    @if ($items->isNotEmpty())
        <div class="event-container">
            @foreach ($items as $item)
                @include($partial, [$itemName => $item])
            @endforeach
        </div>
    @else
        <p class="no_message search-empty-message">{{ $emptyText }}</p>
    @endif
</div>
