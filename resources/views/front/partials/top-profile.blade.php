@php($topProfile = $profileLayout ?? $frontLayout)
@php($editableProfileAssets = $editableProfileAssets ?? false)

<div class="relat">
    <div class="cover_page{{ $editableProfileAssets ? ' editable-cover-area' : '' }}">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img
                @if ($editableProfileAssets) id="preview_cover" @endif
                class="cover-photo{{ $editableProfileAssets ? ' editable-cover' : '' }}"
                src="{{ $topProfile['cover'] }}"
                alt=""
            >
        </div>
        @if ($editableProfileAssets)
            <span class="upload_cover_img">Изменить обложку</span>
        @endif
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img
            @if ($editableProfileAssets) id="preview_ava" class="editable-avatar" @endif
            src="{{ $topProfile['avatar'] }}"
            alt=""
        >
        <h3 class="name">
            {{ $topProfile['firstname'] }}
            @if ($topProfile['user'])
                <span class="status_user online" data-num="{{ $topProfile['user']->id }}"></span>
            @endif
            <br>{{ $topProfile['lastname'] }}
        </h3>
        <p class="citation">{{ $topProfile['about'] }}</p>
    </div>
</div>
<div class="clearfix"></div>
