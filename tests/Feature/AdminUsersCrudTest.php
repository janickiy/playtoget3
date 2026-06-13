<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUsersCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('admin');

        Schema::create('admin', function (Blueprint $table): void {
            $table->id();
            $table->string('login')->unique();
            $table->string('password');
            $table->string('name')->nullable();
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('secondname')->nullable();
            $table->string('sex')->nullable();
            $table->date('birthday')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('viber')->nullable();
            $table->string('website')->nullable();
            $table->text('about')->nullable();
            $table->text('about_sport')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('language')->default('ru');
            $table->boolean('confirmed')->default(false);
            $table->boolean('banned')->default(false);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });

        $this->admin = Admin::query()->create([
            'login' => 'admin',
            'password' => 'password',
            'name' => 'Администратор',
            'role' => Admin::ROLE_ADMIN,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('admin');

        parent::tearDown();
    }

    public function test_users_admin_pages_render(): void
    {
        $user = $this->user([
            'email' => 'ivan@example.com',
            'firstname' => 'Иван',
            'lastname' => 'Петров',
            'city' => 'Москва',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Пользователи')
            ->assertSee(route('admin.datatable.users'), false)
            ->assertSee('id="checkAllUsers"', false)
            ->assertSee('id="bulkAction"', false)
            ->assertSee('Применить');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.show', ['id' => $user->id]))
            ->assertOk()
            ->assertSee('Просмотр пользователя')
            ->assertSee('ivan@example.com')
            ->assertSee('Иван Петров');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.edit', ['id' => $user->id]))
            ->assertOk()
            ->assertSee('Редактирование пользователя')
            ->assertSee('name="telegram"', false)
            ->assertSee('name="whatsapp"', false)
            ->assertSee('name="viber"', false);
    }

    public function test_users_datatable_returns_actions_and_checkboxes(): void
    {
        $user = $this->user([
            'email' => 'petr@example.com',
            'firstname' => 'Петр',
            'city' => 'Казань',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.users', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.email', 'petr@example.com');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.users.show', ['id' => $user->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.users.edit', ['id' => $user->id]), $row['actions']);
        $this->assertStringContainsString('js-user-checkbox', $row['checkbox']);
        $this->assertStringContainsString('deleteRow', $row['actions']);
    }

    public function test_user_admin_update_changes_profile_fields(): void
    {
        $user = $this->user([
            'email' => 'old@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.users.edit', ['id' => $user->id]))
            ->put(route('admin.users.update'), [
                'id' => $user->id,
                'email' => 'new@example.com',
                'password' => 'new-password',
                'password_again' => 'new-password',
                'firstname' => 'Алексей',
                'lastname' => 'Иванов',
                'secondname' => 'Петрович',
                'sex' => 'male',
                'birthday' => '1991-05-10',
                'phone' => '+79999999999',
                'contact_email' => 'contact@example.com',
                'telegram' => '@alexey',
                'whatsapp' => '+78888888888',
                'viber' => '+77777777777',
                'website' => 'https://example.com',
                'about' => 'О себе',
                'about_sport' => 'О спорте',
                'country' => 'Россия',
                'region' => 'Москва',
                'city' => 'Москва',
                'language' => 'ru',
                'confirmed' => 1,
                'banned' => 0,
                'deleted' => 0,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('Алексей', $user->firstname);
        $this->assertSame('@alexey', $user->telegram);
        $this->assertSame('+78888888888', $user->whatsapp);
        $this->assertSame('+77777777777', $user->viber);
        $this->assertTrue((bool) $user->confirmed);
        $this->assertFalse((bool) $user->banned);
        $this->assertFalse((bool) $user->deleted);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_single_user_block_unblock_and_delete(): void
    {
        $user = $this->user(['email' => 'status@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.users.block', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'Пользователь заблокирован.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'banned' => 1,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.users.unblock', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'Пользователь разблокирован.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'banned' => 0,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.users.destroy', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'Пользователь удален.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted' => 1,
        ]);
    }

    public function test_bulk_user_actions(): void
    {
        $first = $this->user(['email' => 'first@example.com']);
        $second = $this->user(['email' => 'second@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.bulk'), [
                'action' => 'block',
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseHas('users', ['id' => $first->id, 'banned' => 1]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'banned' => 1]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.bulk'), [
                'action' => 'unblock',
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseHas('users', ['id' => $first->id, 'banned' => 0]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'banned' => 0]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.bulk'), [
                'action' => 'delete',
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseHas('users', ['id' => $first->id, 'deleted' => 1]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'deleted' => 1]);
    }

    public function test_user_admin_validation_requires_valid_email(): void
    {
        $user = $this->user(['email' => 'valid@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.users.edit', ['id' => $user->id]))
            ->put(route('admin.users.update'), [
                'id' => $user->id,
                'email' => '',
                'contact_email' => 'bad-email',
                'password' => 'secret',
                'password_again' => 'another-secret',
            ])
            ->assertRedirect(route('admin.users.edit', ['id' => $user->id]))
            ->assertSessionHasErrors(['email', 'contact_email', 'password_again']);
    }

    /**
     * Создает пользователя с базовыми полями для тестов админского CRUD.
     *
     * @param array<string, mixed> $attributes
     */
    private function user(array $attributes = []): User
    {
        /** @var User $user */
        $user = User::query()->create(array_merge([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'firstname' => null,
            'lastname' => null,
            'language' => 'ru',
            'confirmed' => true,
            'banned' => false,
            'deleted' => false,
        ], $attributes));

        return $user;
    }
}
