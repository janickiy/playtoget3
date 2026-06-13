<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FriendRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class FriendsPageTest extends TestCase
{
    public function test_friends_page_renders_legacy_sections(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $friend = $this->user(2, 'Иван', 'Петров', 'Москва');
        $possibleFriend = $this->user(3, 'Мария', 'Сидорова', 'Санкт-Петербург');

        $this->actingAs($viewer, 'web');

        $this->mock(FriendRepository::class, function (MockInterface $mock) use ($friend, $possibleFriend): void {
            $mock->shouldReceive('possibleFriendsFor')->andReturn(collect([$possibleFriend]));
            $mock->shouldReceive('friendsFor')->andReturn(collect([$friend]));
            $mock->shouldReceive('friendsCountFor')->andReturn(1);
            $mock->shouldReceive('incomingRequestsFor')->andReturn(collect());
            $mock->shouldReceive('incomingRequestsCountFor')->andReturn(0);
            $mock->shouldReceive('outgoingRequestsFor')->andReturn(collect());
            $mock->shouldReceive('outgoingRequestsCountFor')->andReturn(0);
        });

        $this->get('/friends')
            ->assertStatus(200)
            ->assertSee('Возможные друзья')
            ->assertSee('мои друзья')
            ->assertDontSee('Заявки в друзья')
            ->assertDontSee('Исходящие заявки')
            ->assertSee('Мария')
            ->assertSee('Иван')
            ->assertSee('/ajax', false)
            ->assertSee('/profile/1/messages/user/2', false)
            ->assertSee('показать ещё')
            ->assertSee('frontend/js/friends.js', false);
    }

    private function user(int $id, string $firstname, string $lastname, ?string $city = null): User
    {
        $user = new User([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => 'user' . $id . '@example.test',
            'sex' => 'male',
            'city' => $city,
            'status' => 1,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
