@php
    $viewer = $viewer ?? ($frontLayout['user'] ?? auth()->user());
@endphp

<div class="hov video-card" id="video-block-{{ $video['id'] }}">
    <div class="video-box">
        <img
            class="video_prev"
            src="{{ $video['thumb'] }}"
            data-num="{{ $video['id'] }}"
            data-title="{{ $video['description'] }}"
            alt=""
        >
        <div class="transparent" data-num="{{ $video['id'] }}"></div>
        @if (($canManage ?? false) || (($viewer?->id ?? null) && (int) $viewer->id === (int) $video['owner_id']))
            <span class="icons-hid">
                <button
                    type="button"
                    id="my-video-{{ $video['id'] }}"
                    class="remove_video video-delete-button"
                    data-item="{{ $video['id'] }}"
                    data-tooltip="Delete video"
                    aria-label="Delete video"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M8.5 4.5h7l.75 1.5H20v2H4V6h3.75l.75-1.5Zm.25 5h2v8h-2v-8Zm4.5 0h2v8h-2v-8ZM6.5 9h11l-.8 11H7.3L6.5 9Z"/>
                    </svg>
                </button>
            </span>
        @endif
    </div>
    <span class="video-capt"><i></i>{{ $video['views_count'] }}</span>
</div>
