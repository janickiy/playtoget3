<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserStatusLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        Schema::dropIfExists('logs');
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

        Schema::create('logs', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->integer('user_id')->nullable();
            $table->string('ip', 255)->nullable();
            $table->string('user_agent', 255);
            $table->dateTime('last_sign_in_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_successful_login_writes_authorization_log(): void
    {
        $user = $this->user('confirmed@example.com', UserStatus::Confirmed);

        $this->post(route('front.login'), [
            'username' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('front.home'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('logs', [
            'user_id' => $user->id,
            'ip' => '127.0.0.1',
        ]);
        $this->assertNotNull(DB::table('logs')->where('user_id', $user->id)->value('last_sign_in_at'));
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
                'username' => 'Invalid email or password.',
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
