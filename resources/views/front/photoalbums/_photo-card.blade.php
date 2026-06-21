@if ($photo['small'] && $photo['big'])
    <div class="hov" id="photo-block-{{ $photo['id'] }}">
        <a class="photo_big" title="{{ $photo['description'] }}" href="{{ $photo['big'] }}" data-num="{{ $photo['id'] }}">
            <img src="{{ $photo['small'] }}" alt="">
            <div class="transparent"></div>
        </a>
        @if (($canManage ?? false) || (($viewer?->id ?? null) && (int) $viewer->id === (int) $photo['owner_id']))
            <span class="icons-hid">
                <button
                    type="button"
                    id="my-video-{{ $photo['id'] }}"
                    class="remove_pic photo-delete-button"
                    data-item="{{ $photo['id'] }}"
                    data-tooltip="Delete photo"
                    aria-label="Delete photo"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M8.5 4.5h7l.75 1.5H20v2H4V6h3.75l.75-1.5Zm.25 5h2v8h-2v-8Zm4.5 0h2v8h-2v-8ZM6.5 9h11l-.8 11H7.3L6.5 9Z"/>
                    </svg>
                </button>
            </span>
        @endif
    </div>
@endif
