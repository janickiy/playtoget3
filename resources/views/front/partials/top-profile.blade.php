@php($topProfile = $profileLayout ?? $frontLayout)

<div class="relat">
    <div class="cover_page">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $topProfile['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $topProfile['avatar'] }}" alt="">
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
