<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
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
            $table->string('nickname')->nullable();
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
            $table->tinyInteger('status')->default(UserStatus::New->value);
            $table->dateTime('confirmed_at')->nullable();
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
            ->assertSee('Users')
            ->assertSee(route('admin.datatable.users'), false)
            ->assertSee('id="checkAllUsers"', false)
            ->assertSee('users-checkbox-column')
            ->assertSee('"order": [[1, \'desc\']]', false)
            ->assertSee('id="bulkAction"', false)
            ->assertSee('Apply');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.show', ['id' => $user->id]))
            ->assertOk()
            ->assertSee('View user')
            ->assertSee('Avatar')
            ->assertSee('User avatar')
            ->assertSee('ivan@example.com')
            ->assertSee('Иван Петров');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.edit', ['id' => $user->id]))
            ->assertOk()
            ->assertSee('Edit user')
            ->assertSee('Current avatar')
            ->assertSee('User avatar')
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
            'status' => UserStatus::New->value,
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
        $this->assertSame(UserStatus::New->label(), $row['status']);
        $this->assertSame(UserStatus::New->cssColor(), $row['status_css']);
    }

    public function test_users_datatable_searches_by_displayed_created_date(): void
    {
        $target = $this->user(['email' => 'needed@example.com']);
        $target->forceFill([
            'created_at' => '2026-06-11 12:12:00',
            'updated_at' => '2026-06-11 12:12:00',
        ])->save();

        $other = $this->user(['email' => 'other@example.com']);
        $other->forceFill([
            'created_at' => '2026-06-12 12:12:00',
            'updated_at' => '2026-06-12 12:12:00',
        ])->save();

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.users') . '?' . http_build_query($this->datatableParams([
                'checkbox',
                'id',
                'email',
                'firstname',
                'city',
                'status',
                'created_at',
                'actions',
            ], '11/06/2026 12:12')))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.email', 'needed@example.com')
            ->assertJsonPath('data.0.created_at', '11/06/2026 12:12');
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
                'nickname' => 'Петрович',
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
                'status' => UserStatus::Confirmed->value,
                'confirmed_at' => '2026-06-13 10:00:00',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('Алексей', $user->firstname);
        $this->assertSame('@alexey', $user->telegram);
        $this->assertSame('+78888888888', $user->whatsapp);
        $this->assertSame('+77777777777', $user->viber);
        $this->assertSame(UserStatus::Confirmed->value, (int) $user->status);
        $this->assertSame('2026-06-13 10:00:00', $user->confirmed_at->format('Y-m-d H:i:s'));
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_single_user_block_unblock_and_delete(): void
    {
        $user = $this->user(['email' => 'status@example.com']);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.users.block', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'User blocked.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Blocked->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.users.unblock', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'User unblocked.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Confirmed->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.users.destroy', ['id' => $user->id]))
            ->assertOk()
            ->assertJson(['message' => 'User is deleted.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::Deleted->value,
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

        $this->assertDatabaseHas('users', ['id' => $first->id, 'status' => UserStatus::Blocked->value]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'status' => UserStatus::Blocked->value]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.bulk'), [
                'action' => 'unblock',
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseHas('users', ['id' => $first->id, 'status' => UserStatus::Confirmed->value]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'status' => UserStatus::Confirmed->value]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.users.bulk'), [
                'action' => 'delete',
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseHas('users', ['id' => $first->id, 'status' => UserStatus::Deleted->value]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'status' => UserStatus::Deleted->value]);
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
     * Creates a user with base fields for admin CRUD tests.
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
            'status' => UserStatus::Confirmed->value,
            'confirmed_at' => now(),
        ], $attributes));

        return $user;
    }

    /**
     * @param array<int, string> $columns
     * @return array<string, mixed>
     */
    private function datatableParams(array $columns, string $search = ''): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => [
                'value' => $search,
                'regex' => 'false',
            ],
            'columns' => array_map(fn (string $column): array => [
                'data' => $column,
                'name' => $column,
                'searchable' => in_array($column, ['checkbox', 'actions'], true) ? 'false' : 'true',
                'orderable' => in_array($column, ['checkbox', 'actions'], true) ? 'false' : 'true',
                'search' => [
                    'value' => '',
                    'regex' => 'false',
                ],
            ], $columns),
            'order' => [
                [
                    'column' => 1,
                    'dir' => 'asc',
                ],
            ],
        ];
    }
}
