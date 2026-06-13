@if ($friends->isEmpty())
    <p class="community-invite-empty">Нет друзей, которых можно пригласить.</p>
@else
    <div class="community-invite-friends">
        @foreach ($friends as $friend)
            <label class="community-invite-friend">
                <input type="checkbox" class="js-community-invite-check" name="user_ids[]" value="{{ $friend['id'] }}">
                <span class="community-invite-friend-avatar">
                    <img src="{{ $friend['avatar'] }}" alt="">
                </span>
                <span class="community-invite-friend-text">
                    <span class="community-invite-friend-name">{{ $friend['name'] }}</span>
                    @if ($friend['city'])
                        <span class="community-invite-friend-city">{{ $friend['city'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
@endif
