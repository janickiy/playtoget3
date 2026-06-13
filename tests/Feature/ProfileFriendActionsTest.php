<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\UserRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfileFriendActionsTest extends TestCase
{
    public function test_remove_friend_ajax_calls_friend_repository(): void
    {
        $viewer = $this->user(1, 'viewer@example.test');

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeFriendship')->once()->with(1, 2)->andReturn(true);
        });

        $this->actingAs($viewer, 'web')
            ->getJson('/ajax/remove_friend?user_id=2')
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'result' => 'success',
            ]);
    }

    public function test_block_user_ajax_calls_friend_repository(): void
    {
        $viewer = $this->user(1, 'viewer@example.test');
        $profile = $this->user(2, 'profile@example.test');

        $this->mock(UserRepository::class, function (MockInterface $mock) use ($profile): void {
            $mock->shouldReceive('findActive')->once()->with(2)->andReturn($profile);
        });

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('blockUser')->once()->with(1, 2)->andReturn(true);
        });

        $this->actingAs($viewer, 'web')
            ->getJson('/ajax/block_user?user_id=2')
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'result' => 'success',
            ]);
    }

    public function test_unblock_user_ajax_calls_friend_repository(): void
    {
        $viewer = $this->user(1, 'viewer@example.test');

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('unblockUser')->once()->with(1, 2)->andReturn(true);
        });

        $this->actingAs($viewer, 'web')
            ->getJson('/ajax/unblock_user?user_id=2')
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'result' => 'success',
            ]);
    }

    private function user(int $id, string $email): User
    {
        $user = new User([
            'email' => $email,
            'password' => 'secret',
            'firstname' => 'Test',
            'lastname' => 'User',
            'sex' => 'male',
            'status' => 1,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
