<?php

namespace Tests\Feature;

use App\Enums\SportBlockStatus;
use App\Models\SportBlock;
use App\Repositories\SportBlockRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SportBlockStatusVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sport_blocks');
        Schema::enableForeignKeyConstraints();

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
            $table->boolean('active')->default(false);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sport_blocks');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_front_repository_finds_only_visible_sport_blocks(): void
    {
        $new = $this->sportBlock(SportBlockStatus::New, 'Новая площадка');
        $confirmed = $this->sportBlock(SportBlockStatus::Confirmed, 'Подтвержденная площадка');
        $blocked = $this->sportBlock(SportBlockStatus::Blocked, 'Заблокированная площадка');
        $hidden = $this->sportBlock(SportBlockStatus::Hidden, 'Скрытая площадка');

        /** @var SportBlockRepository $repository */
        $repository = app(SportBlockRepository::class);

        $this->assertNotNull($repository->findByType((int) $new->id, 'playground'));
        $this->assertNotNull($repository->findByType((int) $confirmed->id, 'playground'));
        $this->assertNull($repository->findByType((int) $blocked->id, 'playground'));
        $this->assertNull($repository->findByType((int) $hidden->id, 'playground'));
    }

    public function test_front_repository_lists_only_visible_sport_blocks(): void
    {
        $new = $this->sportBlock(SportBlockStatus::New, 'А новая площадка');
        $confirmed = $this->sportBlock(SportBlockStatus::Confirmed, 'Б подтвержденная площадка');
        $this->sportBlock(SportBlockStatus::Blocked, 'В заблокированная площадка');
        $this->sportBlock(SportBlockStatus::Hidden, 'Г скрытая площадка');

        /** @var SportBlockRepository $repository */
        $repository = app(SportBlockRepository::class);

        $this->assertSame(2, $repository->countByType('playground'));
        $this->assertSame(
            [(int) $new->id, (int) $confirmed->id],
            $repository->byType('playground')->pluck('id')->all(),
        );
    }

    private function sportBlock(SportBlockStatus $status, string $name): SportBlock
    {
        /** @var SportBlock $sportBlock */
        $sportBlock = SportBlock::query()->create([
            'type' => 'playground',
            'name' => $name,
            'about' => 'Описание',
            'place' => 'Москва',
            'address' => 'Адрес',
            'phone' => '',
            'email' => '',
            'avatar' => '',
            'website' => '',
            'owner_id' => null,
            'active' => true,
            'status' => $status->value,
        ]);

        return $sportBlock;
    }
}
