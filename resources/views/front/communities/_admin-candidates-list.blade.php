@if ($users->isEmpty())
    <p class="community-admin-empty">No users found.</p>
@else
    <div class="community-admin-candidates">
        @foreach ($users as $user)
            <div class="community-admin-candidate">
                <a class="community-admin-candidate-avatar" href="{{ route('front.profile.show', ['user' => $user['id']]) }}">
                    <img src="{{ $user['avatar'] }}" alt="">
                </a>
                <div class="community-admin-candidate-text">
                    <a class="community-admin-candidate-name" href="{{ route('front.profile.show', ['user' => $user['id']]) }}">
                        {{ $user['name'] ?: $user['email'] }}
                    </a>
                    <span>ID: {{ $user['id'] }}</span>
                    @if ($user['city'])
                        <span>{{ $user['city'] }}</span>
                    @endif
                    <span>{{ $user['role_name'] }}</span>
                </div>
                <button type="button" class="community-admin-candidate-add js-community-admin-add" data-user-id="{{ $user['id'] }}">
                    Add
                </button>
            </div>
        @endforeach
    </div>
@endif
