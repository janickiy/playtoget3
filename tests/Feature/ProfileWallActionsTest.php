<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\ProfileRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfileWallActionsTest extends TestCase
{
    public function test_remove_comment_ajax_calls_profile_repository(): void
    {
        $viewer = $this->user(1);

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('deleteComment')
                ->once()
                ->withArgs(fn (User $user, int $commentId): bool => $user === $viewer && $commentId === 123)
                ->andReturn(true);
        });

        $this->actingAs($viewer, 'web')
            ->postJson('/ajax/removecomment', ['id_comment' => 123])
            ->assertOk()
            ->assertJson(['result' => 'success']);
    }

    private function user(int $id): User
    {
        $user = new User([
            'email' => 'user' . $id . '@example.test',
            'password' => 'secret',
            'firstname' => 'Test',
            'lastname' => 'User',
            'sex' => 'male',
            'confirmed' => true,
            'banned' => false,
            'deleted' => false,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
