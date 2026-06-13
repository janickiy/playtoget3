<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Announcement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAnnouncementsCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('announcements');
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

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('slug')->unique();
            $table->boolean('published')->default(true);
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
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_announcements_admin_pages_render(): void
    {
        $announcement = $this->announcement([
            'title' => 'Продам экипировку',
            'text' => '<p>Описание объявления</p>',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Объявления')
            ->assertSee(route('admin.announcements.create'), false)
            ->assertSee(route('admin.datatable.announcements'), false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.announcements.create'))
            ->assertOk()
            ->assertSee('Добавление объявления')
            ->assertSee('name="slug"', false)
            ->assertSee('get_announcement_slug', false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.announcements.show', ['id' => $announcement->id]))
            ->assertOk()
            ->assertSee('Просмотр объявления')
            ->assertSee('Продам экипировку')
            ->assertSee('Описание объявления');
    }

    public function test_announcements_datatable_returns_actions(): void
    {
        $announcement = $this->announcement([
            'title' => 'Тренировка',
            'text' => '<p>Сбор команды</p>',
            'slug' => 'trenirovka',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.announcements', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Тренировка')
            ->assertJsonPath('data.0.published', 'да');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.announcements.show', ['id' => $announcement->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.announcements.edit', ['id' => $announcement->id]), $row['actions']);
        $this->assertStringContainsString('deleteRow', $row['actions']);
    }

    public function test_announcement_admin_store_update_and_delete(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.announcements.store'), [
                'title' => 'Новое объявление',
                'text' => '<p>Текст объявления</p>',
                'slug' => '',
                'published' => 1,
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Новое объявление',
            'slug' => 'novoe-obyavlenie',
            'published' => 1,
        ]);

        $announcement = Announcement::query()->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.announcements.update'), [
                'id' => $announcement->id,
                'title' => 'Обновленное объявление',
                'text' => '<p>Новый текст</p>',
                'slug' => 'custom-announcement',
                'published' => 0,
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Обновленное объявление',
            'slug' => 'custom-announcement',
            'published' => 0,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.announcements.destroy', ['id' => $announcement->id]))
            ->assertOk()
            ->assertJson(['message' => 'Данные успешно удалены.']);

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_announcement_admin_validation_requires_title_text_and_valid_slug(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.announcements.create'))
            ->post(route('admin.announcements.store'), [
                'title' => '',
                'text' => '',
                'slug' => 'bad slug',
            ])
            ->assertRedirect(route('admin.announcements.create'))
            ->assertSessionHasErrors(['title', 'text', 'slug']);

        $this->assertDatabaseCount('announcements', 0);
    }

    /**
     * Создает объявление с базовыми полями.
     *
     * @param array<string, mixed> $attributes
     */
    private function announcement(array $attributes = []): Announcement
    {
        /** @var Announcement $announcement */
        $announcement = Announcement::query()->create(array_merge([
            'title' => 'Объявление',
            'text' => '<p>Текст</p>',
            'slug' => 'obyavlenie',
            'published' => true,
        ], $attributes));

        return $announcement;
    }
}
