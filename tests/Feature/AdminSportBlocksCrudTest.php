<?php

namespace Tests\Feature;

use App\Enums\SportBlockStatus;
use App\Models\Admin;
use App\Models\SportBlock;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSportBlocksCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sport_blocks');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        Schema::create('admin', function (Blueprint $table): void {
            $table->id();
            $table->string('login')->unique();
            $table->string('password');
            $table->string('name')->nullable();
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sport_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->text('about')->nullable();
            $table->string('place', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->string('website')->nullable();
            $table->string('type', 20)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->tinyInteger('status')->default(0);
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sport_blocks');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_sport_blocks_admin_pages_render(): void
    {
        $sportBlock = $this->sportBlock([
            'name' => 'Центральный стадион',
            'about' => 'Описание площадки',
            'avatar' => 'stadium.jpg',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.sport-blocks.index'))
            ->assertOk()
            ->assertSee('Спортивные блоки')
            ->assertDontSee('/cp/sport-blocks/create', false)
            ->assertDontSee('Добавить')
            ->assertSee(route('admin.datatable.sport-blocks'), false);

        $this->actingAs($this->admin, 'admin')
            ->get('/cp/sport-blocks/create')
            ->assertNotFound();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.sport-blocks.show', ['id' => $sportBlock->id]))
            ->assertOk()
            ->assertSee('Просмотр спортивного блока')
            ->assertSee('Аватарка')
            ->assertSee('Центральный стадион')
            ->assertSee('Описание площадки')
            ->assertSee('Подтвержденный')
            ->assertDontSee('stadium.jpg');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.sport-blocks.edit', ['id' => $sportBlock->id]))
            ->assertOk()
            ->assertSee('Редактирование спортивного блока')
            ->assertSee('Текущая аватарка')
            ->assertSee('Центральный стадион')
            ->assertDontSee('form-control" name="avatar"', false)
            ->assertDontSee('form-control" name="owner_id"', false);
    }

    public function test_sport_blocks_datatable_returns_actions_and_row_status_color(): void
    {
        $sportBlock = $this->sportBlock([
            'name' => 'Скрытый фитнес',
            'type' => 'fitness',
            'place' => 'Москва',
            'status' => SportBlockStatus::Hidden->value,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.sport-blocks', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Скрытый фитнес')
            ->assertJsonPath('data.0.type', 'Фитнес')
            ->assertJsonPath('data.0.place', 'Москва')
            ->assertJsonPath('data.0.status', 'Скрытый')
            ->assertJsonPath('data.0.status_css', 'bg-secondary');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.sport-blocks.show', ['id' => $sportBlock->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.sport-blocks.edit', ['id' => $sportBlock->id]), $row['actions']);
        $this->assertStringContainsString('deleteRow', $row['actions']);
    }

    public function test_sport_block_admin_update_and_delete(): void
    {
        $sportBlock = $this->sportBlock([
            'type' => 'playground',
            'name' => 'Новая площадка',
            'avatar' => 'playground.jpg',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.sport-blocks.update'), [
                'id' => $sportBlock->id,
                'type' => 'shop',
                'name' => 'Обновленный магазин',
                'about' => 'Новое описание',
                'place' => 'Казань',
                'address' => 'Новый адрес',
                'phone' => '123',
                'email' => 'shop@example.test',
                'avatar' => 'playground.jpg',
                'website' => '',
                'status' => SportBlockStatus::Blocked->value,
            ])
            ->assertRedirect(route('admin.sport-blocks.index'));

        $this->assertDatabaseHas('sport_blocks', [
            'id' => $sportBlock->id,
            'type' => 'shop',
            'name' => 'Обновленный магазин',
            'place' => 'Казань',
            'avatar' => 'playground.jpg',
            'status' => SportBlockStatus::Blocked->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.sport-blocks.destroy', ['id' => $sportBlock->id]))
            ->assertOk()
            ->assertJson(['message' => 'Данные успешно удалены.']);

        $this->assertDatabaseMissing('sport_blocks', [
            'id' => $sportBlock->id,
        ]);
    }

    public function test_sport_block_admin_validation_requires_type_name_and_status_on_update(): void
    {
        $sportBlock = $this->sportBlock();

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.sport-blocks.edit', ['id' => $sportBlock->id]))
            ->put(route('admin.sport-blocks.update'), [
                'id' => $sportBlock->id,
                'type' => 'bad-type',
                'name' => '',
                'place' => str_repeat('a', 101),
                'email' => 'bad-email',
                'status' => 9,
            ])
            ->assertRedirect(route('admin.sport-blocks.edit', ['id' => $sportBlock->id]))
            ->assertSessionHasErrors(['type', 'name', 'place', 'email', 'status']);

        $this->assertDatabaseHas('sport_blocks', [
            'id' => $sportBlock->id,
            'name' => 'Спортивный блок',
        ]);
    }

    /**
     * Создает спортивный блок с базовыми полями.
     *
     * @param array<string, mixed> $attributes
     */
    private function sportBlock(array $attributes = []): SportBlock
    {
        /** @var SportBlock $sportBlock */
        $sportBlock = SportBlock::query()->create(array_merge([
            'type' => 'playground',
            'name' => 'Спортивный блок',
            'about' => 'Описание',
            'place' => 'Москва',
            'address' => 'Адрес',
            'phone' => '',
            'email' => '',
            'avatar' => '',
            'website' => '',
            'owner_id' => null,
            'status' => SportBlockStatus::Confirmed->value,
        ], $attributes));

        return $sportBlock;
    }
}
