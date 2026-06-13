<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Admin;
use App\Models\Event;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminEventsCrudTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('events');
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

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->dateTime('date_from')->nullable();
            $table->dateTime('date_to')->nullable();
            $table->text('description')->nullable();
            $table->string('sport_type')->nullable();
            $table->string('cover_page')->nullable();
            $table->string('place', 100)->nullable();
            $table->text('address')->nullable();
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
        Schema::dropIfExists('events');
        Schema::dropIfExists('admin');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_events_admin_pages_render(): void
    {
        $event = $this->event([
            'name' => 'Турнир выходного дня',
            'description' => 'Описание мероприятия',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('Мероприятия')
            ->assertDontSee('/cp/events/create', false)
            ->assertDontSee('Добавить')
            ->assertSee(route('admin.datatable.events'), false);

        $this->actingAs($this->admin, 'admin')
            ->get('/cp/events/create')
            ->assertNotFound();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.events.show', ['id' => $event->id]))
            ->assertOk()
            ->assertSee('Просмотр мероприятия')
            ->assertSee('Турнир выходного дня')
            ->assertSee('Описание мероприятия')
            ->assertSee('Подтвержденный');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.events.edit', ['id' => $event->id]))
            ->assertOk()
            ->assertSee('Редактирование мероприятия')
            ->assertSee('Текущая обложка')
            ->assertSee('Турнир выходного дня');
    }

    public function test_events_datatable_returns_actions_and_row_status_color(): void
    {
        $event = $this->event([
            'name' => 'Скрытый забег',
            'place' => 'Москва',
            'sport_type' => 'Бег',
            'status' => EventStatus::Hidden->value,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.events', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Скрытый забег')
            ->assertJsonPath('data.0.place', 'Москва')
            ->assertJsonPath('data.0.sport_type', 'Бег')
            ->assertJsonPath('data.0.status', 'Скрытый')
            ->assertJsonPath('data.0.status_css', 'bg-warning');

        $row = $response->json('data.0');

        $this->assertStringContainsString((string) route('admin.events.show', ['id' => $event->id]), $row['actions']);
        $this->assertStringContainsString((string) route('admin.events.edit', ['id' => $event->id]), $row['actions']);
        $this->assertStringContainsString('deleteRow', $row['actions']);
    }

    public function test_events_datatable_searches_by_displayed_start_date(): void
    {
        $this->event([
            'name' => 'Нужное мероприятие',
            'date_from' => '2026-06-11 12:12:00',
            'date_to' => '2026-06-11 13:12:00',
        ]);
        $this->event([
            'name' => 'Другое мероприятие',
            'date_from' => '2026-06-12 12:12:00',
            'date_to' => '2026-06-12 13:12:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.datatable.events') . '?' . http_build_query($this->datatableParams('11/06/2026 12:12')))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Нужное мероприятие')
            ->assertJsonPath('data.0.date_from', '11/06/2026 12:12');
    }

    public function test_event_admin_update_and_delete(): void
    {
        $event = $this->event([
            'name' => 'Новое мероприятие',
            'date_from' => '2026-06-13 10:00:00',
            'date_to' => '2026-06-13 12:00:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.events.update'), [
                'id' => $event->id,
                'name' => 'Обновленное мероприятие',
                'date_from' => '2026-06-14T09:30',
                'date_to' => '2026-06-14T10:30',
                'description' => 'Новое описание',
                'sport_type' => 'Хоккей',
                'cover_page' => '',
                'place' => 'Казань',
                'address' => 'Ледовая арена',
                'status' => EventStatus::Blocked->value,
            ])
            ->assertRedirect(route('admin.events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Обновленное мероприятие',
            'place' => 'Казань',
            'sport_type' => 'Хоккей',
            'status' => EventStatus::Blocked->value,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson(route('admin.events.destroy', ['id' => $event->id]))
            ->assertOk()
            ->assertJson(['message' => 'Данные успешно удалены.']);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_event_admin_validation_requires_name_and_valid_status_on_update(): void
    {
        $event = $this->event();

        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.events.edit', ['id' => $event->id]))
            ->put(route('admin.events.update'), [
                'id' => $event->id,
                'name' => '',
                'date_from' => '2026-06-14T10:00',
                'date_to' => '2026-06-13T10:00',
                'place' => str_repeat('a', 101),
                'status' => 9,
            ])
            ->assertRedirect(route('admin.events.edit', ['id' => $event->id]))
            ->assertSessionHasErrors(['name', 'date_to', 'place', 'status']);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Мероприятие',
        ]);
    }

    /**
     * Создает мероприятие с базовыми полями.
     *
     * @param array<string, mixed> $attributes
     */
    private function event(array $attributes = []): Event
    {
        /** @var Event $event */
        $event = Event::query()->create(array_merge([
            'name' => 'Мероприятие',
            'date_from' => '2026-06-13 10:00:00',
            'date_to' => '2026-06-13 12:00:00',
            'description' => 'Описание',
            'sport_type' => 'Футбол',
            'cover_page' => '',
            'place' => 'Москва',
            'address' => 'Адрес',
            'status' => EventStatus::Confirmed->value,
        ], $attributes));

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function datatableParams(string $search = ''): array
    {
        $columns = collect([
            'id',
            'name',
            'place',
            'sport_type',
            'date_from',
            'date_to',
            'status',
            'created_at',
            'actions',
        ])->map(fn (string $column): array => [
            'data' => $column,
            'name' => $column,
            'searchable' => $column === 'actions' ? 'false' : 'true',
            'orderable' => $column === 'actions' ? 'false' : 'true',
            'search' => [
                'value' => '',
                'regex' => 'false',
            ],
        ])->all();

        return [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => [
                'value' => $search,
                'regex' => 'false',
            ],
            'columns' => $columns,
            'order' => [
                [
                    'column' => 0,
                    'dir' => 'asc',
                ],
            ],
        ];
    }
}
