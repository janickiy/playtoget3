<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserStatusLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->tinyInteger('status')->default(UserStatus::New->value);
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_blocked_deleted_and_invalid_password_use_same_login_error(): void
    {
        $confirmed = $this->user('confirmed@example.com', UserStatus::Confirmed);
        $blocked = $this->user('blocked@example.com', UserStatus::Blocked);
        $deleted = $this->user('deleted@example.com', UserStatus::Deleted);

        $this->assertInvalidLoginMessage($confirmed->email, 'wrong-password');
        $this->assertInvalidLoginMessage($blocked->email, 'password');
        $this->assertInvalidLoginMessage($deleted->email, 'password');
        $this->assertInvalidLoginMessage('missing@example.com', 'password');
    }

    private function assertInvalidLoginMessage(string $email, string $password): void
    {
        $this->from(route('front.home'))
            ->post(route('front.login'), [
                'username' => $email,
                'password' => $password,
            ])
            ->assertRedirect(route('front.home'))
            ->assertSessionHasErrors([
                'username' => 'Неверный email или пароль.',
            ]);
    }

    private function user(string $email, UserStatus $status): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => $status->value,
            'confirmed_at' => $status === UserStatus::Confirmed ? now() : null,
        ]);

        return $user;
    }
}
