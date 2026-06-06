<div class="relat">
    <div class="cover_page">
        <div class="cover-container">
            <div class="cover_back"></div>
            <img class="cover-photo" src="{{ $teamData['cover'] }}" alt="">
        </div>
    </div>
    <div class="clearfix"></div>
    <div id="top-top" class="account top_thumb_avatar">
        <img border="0" src="{{ $teamData['avatar'] }}" alt="">
        <h3 class="name">
            {{ $teamData['name'] }}
            <br>
            @if ($teamData['sport_type'])
                ({{ $teamData['sport_type'] }})
            @endif
        </h3>
        <p class="citation">{{ $teamData['place'] }}</p>
    </div>
</div>
<div class="clearfix"></div>

@if ($teamData['about'])
    <div class="sport_group_title">{{ $teamData['about'] }}</div>
@endif

<ul class="sport_group_list">
    <li><a href="{{ route('front.teams.show', ['community' => $team->id]) }}" @class(['active-link' => $section === 'members'])><i class="icon_list icon-5"></i><span>Участники</span></a></li>
    @if ($permissions['photo'])
        <li><a href="{{ route('front.teams.photoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'photoalbums'])><i class="icon_list icon-2"></i><span>Фотографии</span></a></li>
    @endif
    @if ($permissions['video'])
        <li><a href="{{ route('front.teams.videoalbums', ['community' => $team->id]) }}" @class(['active-link' => $section === 'videoalbums'])><i class="icon_list icon-3"></i><span>Видео</span></a></li>
    @endif
    <li><a href="{{ route('front.teams.events', ['community' => $team->id]) }}" @class(['active-link' => $section === 'events'])><i class="icon_list icon-1"></i><span>Мероприятия</span></a></li>
    @if ($canManageTeam ?? false)
        <li><a href="{{ route('front.teams.edit', ['community' => $team->id]) }}" @class(['active-link' => $section === 'edit'])><i class="icon_list icon-4"></i><span>Редактировать</span></a></li>
    @endif
</ul>
