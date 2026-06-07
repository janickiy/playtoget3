<div class="relat">
    <div class="cover_page">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $groupData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $groupData['avatar'] }}" alt="">
        <h3 class="name">
            {{ $groupData['name'] }}
            <br>
            @if ($groupData['sport_type'])
                ({{ $groupData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $groupData['place'] }}</p>
    </div>
</div>
<div class="clearfix"></div>

@if ($groupData['about'])
    <div class="sport_group_title">{{ $groupData['about'] }}</div>
@endif

<ul class="sport_group_list">
    <li><a href="{{ route('front.groups.show', ['community' => $group->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Участники</span></a></li>
    @if ($canManageGroup ?? false)
        <li><a href="{{ route('front.groups.edit', ['community' => $group->id]) }}" @class(['active-link' => $section === 'edit'])><i class="icon_list icon-4"></i><span>Редактировать</span></a></li>
    @endif
</ul>
