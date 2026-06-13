<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnnouncementsFrontTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('announcements');

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('slug')->unique();
            $table->boolean('published')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('announcements');

        parent::tearDown();
    }

    public function test_announcements_sidebar_shows_three_latest_published_items(): void
    {
        $this->announcement('Первое объявление', 'first', true, now()->subDays(4));
        $this->announcement('Второе объявление', 'second', true, now()->subDays(3));
        $this->announcement('Третье объявление', 'third', true, now()->subDays(2));
        $this->announcement('Четвертое объявление', 'fourth', true, now()->subDay());
        $this->announcement('Скрытое объявление', 'hidden', false, now());

        $this->get(route('front.announcements.show', ['slug' => 'fourth']))
            ->assertOk()
            ->assertSee('Объявления')
            ->assertSee(route('front.announcements.show', ['slug' => 'fourth']), false)
            ->assertSee(route('front.announcements.show', ['slug' => 'third']), false)
            ->assertSee(route('front.announcements.show', ['slug' => 'second']), false)
            ->assertDontSee(route('front.announcements.show', ['slug' => 'first']), false)
            ->assertDontSee('Скрытое объявление');
    }

    public function test_announcements_index_uses_static_page_layout(): void
    {
        $this->announcement('Открытое объявление', 'open-announcement');

        $this->get(route('front.announcements.index'))
            ->assertOk()
            ->assertSee('Открытое объявление')
            ->assertDontSee('class="cover_page', false)
            ->assertDontSee('id="top-top"', false);
    }

    public function test_announcement_page_is_loaded_by_slug_only_when_published(): void
    {
        $this->announcement('Открытое объявление', 'open-announcement', true);
        $this->announcement('Скрытое объявление', 'draft-announcement', false);

        $this->assertSame('/announcements/open-announcement', route('front.announcements.show', ['slug' => 'open-announcement'], false));

        $this->get(route('front.announcements.show', ['slug' => 'open-announcement']))
            ->assertOk()
            ->assertSee('Открытое объявление')
            ->assertSee('Текст объявления')
            ->assertDontSee('class="cover_page', false)
            ->assertDontSee('id="top-top"', false);

        $this->get(route('front.announcements.show', ['slug' => 'draft-announcement']))
            ->assertOk()
            ->assertSee('Страница не найдена')
            ->assertDontSee('Скрытое объявление');
    }

    private function announcement(string $title, string $slug, bool $published = true, mixed $createdAt = null): Announcement
    {
        /** @var Announcement $announcement */
        $announcement = Announcement::query()->create([
            'title' => $title,
            'text' => '<p>Текст объявления</p>',
            'slug' => $slug,
            'published' => $published,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        return $announcement;
    }
}
