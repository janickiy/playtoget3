<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LikeToggleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('likes');
        Schema::create('likes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('likeable_type')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->dateTime('time')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('likes');

        parent::tearDown();
    }

    public function test_liked_ajax_toggles_photo_video_and_comment_likes(): void
    {
        $viewer = $this->user(1);
        $this->actingAs($viewer, 'web');

        foreach (['photo' => 10, 'video' => 20, 'comment' => 30] as $type => $contentId) {
            $this->getJson('/ajax/liked?id=' . $contentId . '&likeable_type=' . $type)
                ->assertOk()
                ->assertJson([
                    'result' => 1,
                    'liked' => true,
                ]);

            $this->assertSame(1, $this->likesCount($viewer, $type, $contentId));

            $this->getJson('/ajax/liked?id=' . $contentId . '&likeable_type=' . $type)
                ->assertOk()
                ->assertJson([
                    'result' => 0,
                    'liked' => false,
                ]);

            $this->assertSame(0, $this->likesCount($viewer, $type, $contentId));
        }
    }

    public function test_liked_ajax_removes_existing_duplicate_like_rows(): void
    {
        $viewer = $this->user(1);
        $this->actingAs($viewer, 'web');

        Like::query()->create([
            'user_id' => $viewer->id,
            'content_id' => 10,
            'likeable_type' => 'photo',
            'time' => now(),
        ]);
        Like::query()->create([
            'user_id' => $viewer->id,
            'content_id' => 10,
            'likeable_type' => 'photo',
            'time' => now(),
        ]);

        $this->getJson('/ajax/liked?id=10&likeable_type=photo')
            ->assertOk()
            ->assertJson([
                'result' => 0,
                'liked' => false,
            ]);

        $this->assertSame(0, $this->likesCount($viewer, 'photo', 10));
    }

    private function likesCount(User $viewer, string $type, int $contentId): int
    {
        return Like::query()
            ->where('user_id', $viewer->id)
            ->where('likeable_type', $type)
            ->where('content_id', $contentId)
            ->count();
    }

    private function user(int $id): User
    {
        $user = new User([
            'email' => 'user' . $id . '@example.test',
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
