@if ($profileUser->isActive())
    @php
        $isOwnPage = $viewer && (int) $viewer->id === (int) $profileUser->id;
    @endphp

    <div id="information">
        <ul>
            @if ($profileData['last_visit'] !== '')
                <li><span>Last seen</span><div>{{ $profileData['last_visit'] }}</div></li>
            @endif

            @foreach ($profileData['sport_types'] as $sport)
                @if ($sport['sport_type'] !== '')
                    <li><span>Sport type</span><div>{{ $sport['sport_type'] }}</div></li>
                @endif
                @if ($sport['sport_level'] !== '')
                    <li><span>Level</span><div>{{ $sport['sport_level'] }}</div></li>
                @endif
                <li><span>Looking for a team</span><div>{{ $sport['search_team'] }}</div></li>
            @endforeach

            @if ($profileData['about_sport'] !== '')
                <li><span>Sports achievements</span><div class="achivment-list">{{ $profileData['about_sport'] }}</div></li>
            @endif
        </ul>

        <ul class="more-info">
            <li><hr></li>
            @if ($profileData['birthday'] !== '')
                <li><span>Date of birth</span><div>{{ $profileData['birthday'] }}</div></li>
            @endif
            @if ($profileData['city'] !== '')
                <li><span>City</span><div>{{ $profileData['city'] }}</div></li>
            @endif
            @if (($profileData['country'] ?? '') !== '')
                <li><span>Country</span><div>{{ $profileData['country'] }}</div></li>
            @endif
            @if (($profileData['region'] ?? '') !== '')
                <li><span>Region</span><div>{{ $profileData['region'] }}</div></li>
            @endif
            @if ($profileData['phone'] !== '')
                <li><span>Phone</span><div>{{ $profileData['phone'] }}</div></li>
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
                <li><span>Personal website</span><div>{{ $profileData['website'] }}</div></li>
            @endif

            @if ($profileData['education']->isNotEmpty())
                <li>
                    <span>Education</span>
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
                    <span>Workplace</span>
                    @foreach ($profileData['work'] as $work)
                        <div>
                            {{ $work['name'] }}<br>
                        </div>
                    @endforeach
                </li>
            @endif
        </ul>

        <hr>
        <a class="minimax" onclick="return false"><i>expand</i><i>collapse</i></a>
    </div>

    @unless ($isOwnPage)
        <div class="profilelink">
            @if ($permissions['photo'])
                <a href="{{ route('front.photoalbums.user', ['user' => $profileUser->id]) }}"><span>Photos</span></a>
            @endif
            @if ($permissions['video'])
                <a href="{{ route('front.videoalbums.user', ['user' => $profileUser->id]) }}"><span>Video</span></a>
            @endif
            @if ($permissions['friends'])
                <a href="{{ route('front.friends.user', ['user' => $profileUser->id]) }}"><span>Friends</span></a>
            @endif
            @if ($permissions['teams'])
                <a href="{{ route('front.teams.user', ['user' => $profileUser->id]) }}"><span>Teams</span></a>
            @endif
        </div>
    @endunless
@endif
