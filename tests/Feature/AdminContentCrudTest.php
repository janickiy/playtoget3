<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Content;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminContentCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('content');
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

        Schema::create('content', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('text')->nullable();
            $table->boolean('published')->default(true);
            $table->string('slug')->unique();
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
        Schema::dropIfExists('content');
        Schema::dropIfExists('admin');

        parent::tearDown();
    }

    public function test_content_admin_pages_render(): void
    {
        $page = Content::query()->create([
            'title' => 'О проекте',
            'text' => '<p>Описание</p>',
            'slug' => 'o-proekte',
            'published' => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.content.index'))
            ->assertOk()
            ->assertSee('Страницы и разделы')
            ->assertSee(route('admin.content.create'), false)
            ->assertSee(route('admin.datatable.content'), false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.content.create'))
            ->assertOk()
            ->assertSee('Добавление раздела')
            ->assertSee('name="slug"', false)
            ->assertSee('rows="5"', false)
            ->assertSee('height: 120', false)
            ->assertSee('min-height: 120', false)
            ->assertSee('resize: vertical', false)
            ->assertSee('get_page_slug', false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.content.show', ['id' => $page->id]))
            ->assertOk()
            ->assertSee('О проекте')
            ->assertSee('Описание');
    }

    public function test_admin_layout_does_not_render_brand_logo_and_language_selector(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard.index'))
            ->assertOk()
            ->assertDontSee('AdminLTELogo', false)
            ->assertDontSee('brand-link', false)
            ->assertDontSee('brand-image', false)
            ->assertDontSee('brand-text', false)
            ->assertDontSee('flag-icon', false)
            ->assertDontSee('select-lang', false)
            ->assertDontSee('English')
            ->assertDontSee('Русский (Russian)')
            ->assertSee('title="выйти"', false);
    }

    public function test_content_edit_page_does_not_auto_update_slug(): void
    {
        $page = Content::query()->create([
            'title' => 'О проекте',
            'text' => '<p>Описание</p>',
            'slug' => 'o-proekte',
            'published' => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.content.edit', ['id' => $page->id]))
            ->assertOk()
            ->assertDontSee('get_page_slug', false)
            ->assertDontSee('$("#title").on("change keyup input"', false);
    }

    public function test_front_content_page_is_loaded_by_slug_only_when_published(): void
    {
        Content::query()->create([
            'title' => 'О сервисе',
            'text' => '<p>Опубликовано</p>',
            'slug' => 'o-servise',
            'published' => true,
        ]);

        Content::query()->create([
            'title' => 'Черновик',
            'text' => '<p>Скрытый текст</p>',
            'slug' => 'draft-page',
            'published' => false,
        ]);

        $this->assertSame('/page/o-servise', route('front.content.show', ['slug' => 'o-servise'], false));

        $this->get(route('front.content.show', ['slug' => 'o-servise']))
            ->assertOk()
            ->assertSee('О сервисе')
            ->assertSee('Опубликовано');

        $this->get(route('front.content.show', ['slug' => 'draft-page']))
            ->assertOk()
            ->assertSee('Страница не найдена')
            ->assertDontSee('Скрытый текст');

        $this->get('/page/1')
            ->assertOk()
            ->assertSee('Страница не найдена')
            ->assertDontSee('Опубликовано');
    }

    public function test_content_admin_store_update_and_delete(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.content.store'), [
                'title' => 'Новая страница',
                'text' => '<p>Текст страницы</p>',
                'slug' => '',
                'published' => 1,
            ])
            ->assertRedirect(route('admin.content.index'));

        $this->assertDatabaseHas('content', [
            'title' => 'Новая страница',
            'slug' => 'novaya-stranitsa',
            'published' => 1,
        ]);

        $page = Content::query()->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.content.update'), [
                'id' => $page->id,
                'title' => 'Обновленная страница',
                'text' => '<p>Новый текст</p>',
                'slug' => 'custom-page',
                'published' => 0,
            ])
            ->assertRedirect(route('admin.content.index'));

        $this->assertDatabaseHas('content', [
            'id' => $page->id,
            'title' => 'Обновленная страница',
            'slug' => 'custom-page',
            'published' => 0,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.content.destroy', ['id' => $page->id]))
            ->assertOk()
            ->assertJson(['message' => 'Данные успешно удалены.']);

        $this->assertDatabaseMissing('content', [
            'id' => $page->id,
        ]);
    }

    public function test_content_admin_validation_requires_title_and_text(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.content.create'))
            ->post(route('admin.content.store'), [
                'title' => '',
                'text' => '',
                'slug' => 'bad slug',
            ])
            ->assertRedirect(route('admin.content.create'))
            ->assertSessionHasErrors(['title', 'text', 'slug']);

        $this->assertDatabaseCount('content', 0);
    }
}
