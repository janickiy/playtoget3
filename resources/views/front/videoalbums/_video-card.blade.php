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
                <i
                    id="my-video-{{ $video['id'] }}"
                    class="remove_video"
                    data-item="{{ $video['id'] }}"
                    data-tooltip="Delete video"
                >
                    <img src="{{ asset('frontend/images/icon-krest.png') }}" alt="">
                </i>
            </span>
        @endif
    </div>
    <span class="video-capt"><i></i>{{ $video['views_count'] }}</span>
</div>
