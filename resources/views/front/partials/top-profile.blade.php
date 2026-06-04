<div class="relat">
    <div class="cover_page">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $frontLayout['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $frontLayout['avatar'] }}" alt="">
        <h3 class="name">
            {{ $frontLayout['firstname'] }}
            @if ($frontLayout['user'])
                <span class="status_user online" data-num="{{ $frontLayout['user']->id }}"></span>
            @endif
            <br>{{ $frontLayout['lastname'] }}
        </h3>
        <p class="citation">{{ $frontLayout['about'] }}</p>
    </div>
</div>
<div class="clearfix"></div>
