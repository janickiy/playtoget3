@if (! $profileUser->banned && ! $profileUser->deleted)
    @php
        $isOwnPage = $viewer && (int) $viewer->id === (int) $profileUser->id;
    @endphp

    <div id="information">
        <ul>
            @if ($profileData['last_visit'] !== '')
                <li><span>Был(a) на сайте</span><div>{{ $profileData['last_visit'] }}</div></li>
            @endif

            @foreach ($profileData['sport_types'] as $sport)
                @if ($sport['sport_type'] !== '')
                    <li><span>Вид спорта</span><div>{{ $sport['sport_type'] }}</div></li>
                @endif
                @if ($sport['sport_level'] !== '')
                    <li><span>Уровень</span><div>{{ $sport['sport_level'] }}</div></li>
                @endif
                <li><span>Ищу команду</span><div>{{ $sport['search_team'] }}</div></li>
            @endforeach

            @if ($profileData['about_sport'] !== '')
                <li><span>Спортивные достижения</span><div class="achivment-list">{{ $profileData['about_sport'] }}</div></li>
            @endif
        </ul>

        <ul class="more-info">
            <li><hr></li>
            @if ($profileData['birthday'] !== '')
                <li><span>Дата рождения</span><div>{{ $profileData['birthday'] }}</div></li>
            @endif
            @if ($profileData['city'] !== '')
                <li><span>Город</span><div>{{ $profileData['city'] }}</div></li>
            @endif
            @if ($profileData['phone'] !== '')
                <li><span>Телефон</span><div>{{ $profileData['phone'] }}</div></li>
            @endif
            @if ($profileData['contact_email'] !== '')
                <li><span>Email</span><div>{{ $profileData['contact_email'] }}</div></li>
            @endif
            @if ($profileData['telegram'] !== '')
                <li><span>Telegram</span><div>{{ $profileData['telegram'] }}</div></li>
            @endif
            @if ($profileData['whatsapp'] !== '')
                <li><span>WhatsApp</span><div>{{ $profileData['whatsapp'] }}</div></li>
            @endif
            @if ($profileData['viber'] !== '')
                <li><span>Viber</span><div>{{ $profileData['viber'] }}</div></li>
            @endif
            @if ($profileData['website'] !== '')
                <li><span>Личный сайт</span><div>{{ $profileData['website'] }}</div></li>
            @endif

            @if ($profileData['education']->isNotEmpty())
                <li>
                    <span>Образование</span>
                    @foreach ($profileData['education'] as $education)
                        <div>
                            {{ $education['name'] }}<br>
                            {{ $education['period'] }}
                        </div>
                    @endforeach
                </li>
            @endif

            @if ($profileData['work']->isNotEmpty())
                <li>
                    <span>Место работы</span>
                    @foreach ($profileData['work'] as $work)
                        <div>
                            {{ $work['name'] }}<br>
                        </div>
                    @endforeach
                </li>
            @endif
        </ul>

        <hr>
        <a class="minimax" onclick="return false"><i>развернуть</i><i>свернуть</i></a>
    </div>

    @unless ($isOwnPage)
        <div class="profilelink">
            @if ($permissions['photo'])
                <a href="{{ route('front.photoalbums.user', ['user' => $profileUser->id]) }}"><span>Фотографии</span></a>
            @endif
            @if ($permissions['video'])
                <a href="{{ route('front.videoalbums.user', ['user' => $profileUser->id]) }}"><span>Видео</span></a>
            @endif
            @if ($permissions['friends'])
                <a href="{{ route('front.friends.user', ['user' => $profileUser->id]) }}"><span>Друзья</span></a>
            @endif
            @if ($permissions['teams'])
                <a href="{{ route('front.teams.user', ['user' => $profileUser->id]) }}"><span>Команды</span></a>
            @endif
        </div>
    @endunless
@endif
