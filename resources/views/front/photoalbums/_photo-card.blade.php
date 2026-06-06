@if ($photo['small'] && $photo['big'])
    <div class="hov" id="photo-block-{{ $photo['id'] }}">
        <a class="photo_big" title="{{ $photo['description'] }}" href="{{ $photo['big'] }}" data-lightbox="roadtrip" data-num="{{ $photo['id'] }}">
            <img src="{{ $photo['small'] }}" alt="">
            <div class="transparent"></div>
        </a>
        @if (($canManage ?? false) || (($viewer?->id ?? null) && (int) $viewer->id === (int) $photo['owner_id']))
            <span class="icons-hid">
                <i
                    id="my-video-{{ $photo['id'] }}"
                    class="remove_pic"
                    data-item="{{ $photo['id'] }}"
                    data-tooltip="Удалить фото"
                >
                    <img src="{{ asset('frontend/images/icon-krest.png') }}" alt="">
                </i>
            </span>
        @endif
    </div>
@endif
