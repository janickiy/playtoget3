<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Repositories\EventRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventStatusVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('accepted_event_members');
        Schema::dropIfExists('events');
        Schema::enableForeignKeyConstraints();

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

        Schema::create('accepted_event_members', function (Blueprint $table): void {
            $table->id();
            $table->string('eventable_type', 50)->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedTinyInteger('role')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('accepted_event_members');
        Schema::dropIfExists('events');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_front_repository_finds_only_confirmed_events(): void
    {
        $confirmed = $this->event(EventStatus::Confirmed, 'Подтвержденное мероприятие');
        $new = $this->event(EventStatus::New, 'Новое мероприятие');
        $blocked = $this->event(EventStatus::Blocked, 'Заблокированное мероприятие');

        /** @var EventRepository $repository */
        $repository = app(EventRepository::class);

        $this->assertNotNull($repository->findActive((int) $confirmed->id));
        $this->assertNull($repository->findActive((int) $new->id));
        $this->assertNull($repository->findActive((int) $blocked->id));
    }

    public function test_front_repository_lists_only_confirmed_events(): void
    {
        $confirmed = $this->event(EventStatus::Confirmed, 'Подтвержденное мероприятие');
        $this->event(EventStatus::New, 'Новое мероприятие');
        $this->event(EventStatus::Blocked, 'Заблокированное мероприятие');

        /** @var EventRepository $repository */
        $repository = app(EventRepository::class);

        $this->assertSame(1, $repository->popularEventsCount());
        $this->assertSame([(int) $confirmed->id], $repository->popularEvents(10)->pluck('id')->all());
    }

    private function event(EventStatus $status, string $name): Event
    {
        /** @var Event $event */
        $event = Event::query()->create([
            'name' => $name,
            'date_from' => '2026-06-13 10:00:00',
            'date_to' => '2026-06-13 12:00:00',
            'description' => 'Описание',
            'sport_type' => 'Футбол',
            'cover_page' => '',
            'place' => 'Москва',
            'address' => 'Адрес',
            'status' => $status->value,
        ]);

        return $event;
    }
}
