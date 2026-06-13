<?php

namespace Tests\Feature;

use App\Enums\CommunityStatus;
use App\Models\Admin;
use App\Models\Community;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCommunitiesCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('communities');
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

        Schema::create('communities', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('name')->nullable();
            $table->text('about')->nullable();
            $table->timestamps();
            $table->string('avatar')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('place', 100)->nullable();
            $table->string('sport_type')->nullable();
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
        Schema::dropIfExists('communities');
        Schema::dropIfExists('admin');

        parent::tearDown();
    }

    public function test_communities_admin_pages_render(): void
    {
        $community = $this->community([
            'name' => 'PlayToGet',
            'about' => 'Описание комьюнити',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.communities.index'))
            ->assertOk()
            ->assertSee('Комьюнити')
            ->assertDontSee('/cp/communities/create', false)
            ->assertDontSee('Добавить')
            ->assertSee(route('admin.datatable.communities'), false);

        $this->actingAs($this->admin, 'admin')
            ->get('/cp/communities/create')
            ->assertNotFound();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.communities.show', ['id' => $community->id]))
            ->assertOk()
            ->assertSee('Просмотр комьюнити')
            ->assertSee('Аватарка')
            ->assertSee('Подтвержденный')
            ->assertSee('PlayToGet')
            ->assertSee('Описание комьюнити');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.communities.edit', ['id' => $community->id]))
            ->assertOk()
            ->assertSee('Редактирование комьюнити')
            ->assertSee('Текущая аватарка')
            ->assertSee('PlayToGet');
    }

    public function test_communities_datatable_returns_actions(): void
    {
        $community = $this->community([
            'name' => 'Команда тест',
            'type' => 'team',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.communities', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Команда тест')
            ->assertJsonPath('data.0.type', 'Команда')
            ->assertJsonPath('data.0.status', 'Подтвержденный')
            ->assertJsonPath('data.0.status_css', '');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.communities.show', ['id' => $community->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.communities.edit', ['id' => $community->id]), $row['actions']);
        $this->assertStringContainsString('deleteRow', $row['actions']);
    }

    public function test_communities_datatable_searches_by_displayed_created_date(): void
    {
        $target = $this->community(['name' => 'Нужное комьюнити']);
        $target->forceFill([
            'created_at' => '2026-06-11 12:12:00',
            'updated_at' => '2026-06-11 12:12:00',
        ])->save();

        $other = $this->community(['name' => 'Другое комьюнити']);
        $other->forceFill([
            'created_at' => '2026-06-12 12:12:00',
            'updated_at' => '2026-06-12 12:12:00',
        ])->save();

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.communities') . '?' . http_build_query($this->datatableParams([
                'id',
                'type',
                'name',
                'place',
                'sport_type',
                'status',
                'created_at',
                'actions',
            ], '11/06/2026 12:12')))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Нужное комьюнити')
            ->assertJsonPath('data.0.created_at', '11/06/2026 12:12');
    }

    public function test_community_admin_update_and_delete(): void
    {
        $community = $this->community([
            'type' => 'group',
            'name' => 'Новая группа',
            'about' => 'Описание группы',
            'avatar' => 'avatar.jpg',
            'cover_page' => 'cover.jpg',
            'place' => 'Казань',
            'sport_type' => 'Бег',
            'status' => CommunityStatus::Confirmed->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.communities.update'), [
                'id' => $community->id,
                'type' => 'team',
                'name' => 'Обновленная команда',
                'about' => 'Новое описание',
                'avatar' => '',
                'cover_page' => '',
                'place' => 'Москва',
                'sport_type' => 'Хоккей',
                'status' => CommunityStatus::Blocked->value,
            ])
            ->assertRedirect(route('admin.communities.index'));

        $this->assertDatabaseHas('communities', [
            'id' => $community->id,
            'type' => 'team',
            'name' => 'Обновленная команда',
            'place' => 'Москва',
            'sport_type' => 'Хоккей',
            'status' => CommunityStatus::Blocked->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.communities.destroy', ['id' => $community->id]))
            ->assertOk()
            ->assertJson(['message' => 'Данные успешно удалены.']);

        $this->assertDatabaseMissing('communities', [
            'id' => $community->id,
        ]);
    }

    public function test_community_admin_validation_requires_type_and_name_on_update(): void
    {
        $community = $this->community();

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.communities.edit', ['id' => $community->id]))
            ->put(route('admin.communities.update'), [
                'id' => $community->id,
                'type' => 'bad-type',
                'name' => '',
                'place' => str_repeat('a', 101),
            ])
            ->assertRedirect(route('admin.communities.edit', ['id' => $community->id]))
            ->assertSessionHasErrors(['type', 'name', 'place']);

        $this->assertDatabaseHas('communities', [
            'id' => $community->id,
            'name' => 'Комьюнити',
        ]);
    }

    /**
     * Создает комьюнити с базовыми полями.
     *
     * @param array<string, mixed> $attributes
     */
    private function community(array $attributes = []): Community
    {
        /** @var Community $community */
        $community = Community::query()->create(array_merge([
            'type' => 'team',
            'status' => CommunityStatus::Confirmed->value,
            'name' => 'Комьюнити',
            'about' => 'Описание',
            'avatar' => '',
            'cover_page' => '',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
        ], $attributes));

        return $community;
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
                'searchable' => $column === 'actions' ? 'false' : 'true',
                'orderable' => $column === 'actions' ? 'false' : 'true',
                'search' => [
                    'value' => '',
                    'regex' => 'false',
                ],
            ], $columns),
            'order' => [
                [
                    'column' => 0,
                    'dir' => 'asc',
                ],
            ],
        ];
    }
}
