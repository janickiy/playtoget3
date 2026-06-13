<?php

namespace Tests\Feature;

use App\Enums\CommunityStatus;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\User;
use App\Repositories\CommunityRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommunityStatusVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('community_roles');
        Schema::dropIfExists('communities_settings');
        Schema::dropIfExists('communities');
        Schema::enableForeignKeyConstraints();

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

        Schema::create('communities_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('permission_wall')->default(false);
            $table->boolean('permission_photo')->default(false);
            $table->boolean('permission_video')->default(false);
            $table->boolean('type')->default(false);
            $table->unsignedBigInteger('community_id')->nullable();
        });

        Schema::create('community_roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('community_id')->nullable();
            $table->unsignedTinyInteger('role')->nullable();
            $table->string('role_description')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('community_roles');
        Schema::dropIfExists('communities_settings');
        Schema::dropIfExists('communities');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_front_repository_hides_blocked_and_hidden_teams_and_groups(): void
    {
        $confirmedTeam = $this->community('team', CommunityStatus::Confirmed, 'Подтвержденная команда');
        $newTeam = $this->community('team', CommunityStatus::New, 'Новая команда');
        $blockedGroup = $this->community('group', CommunityStatus::Blocked, 'Заблокированная группа');
        $hiddenTeam = $this->community('team', CommunityStatus::Hidden, 'Скрытая команда');
        $confirmedGroup = $this->community('group', CommunityStatus::Confirmed, 'Подтвержденная группа');

        /** @var CommunityRepository $repository */
        $repository = app(CommunityRepository::class);

        $this->assertNotNull($repository->findTeam((int) $confirmedTeam->id));
        $this->assertNotNull($repository->findTeam((int) $newTeam->id));
        $this->assertNull($repository->findTeam((int) $hiddenTeam->id));
        $this->assertNull($repository->findGroup((int) $blockedGroup->id));
        $this->assertNotNull($repository->findGroup((int) $confirmedGroup->id));
    }

    public function test_front_repository_lists_only_visible_communities(): void
    {
        $confirmedTeam = $this->community('team', CommunityStatus::Confirmed, 'Подтвержденная команда');
        $newTeam = $this->community('team', CommunityStatus::New, 'Новая команда');
        $this->community('team', CommunityStatus::Blocked, 'Заблокированная команда');
        $this->community('team', CommunityStatus::Hidden, 'Скрытая команда');

        /** @var CommunityRepository $repository */
        $repository = app(CommunityRepository::class);

        $this->assertSame(2, $repository->popularTeamsCount());
        $this->assertEqualsCanonicalizing(
            [(int) $confirmedTeam->id, (int) $newTeam->id],
            $repository->popularTeams(10)->pluck('id')->all()
        );
    }

    public function test_owner_cannot_leave_own_community_but_member_can_leave(): void
    {
        $community = $this->community('team', CommunityStatus::Confirmed, 'Команда владельца');
        $owner = $this->user(10);
        $member = $this->user(11);

        CommunityRole::query()->create([
            'community_id' => $community->id,
            'user_id' => $owner->id,
            'role' => 1,
        ]);
        CommunityRole::query()->create([
            'community_id' => $community->id,
            'user_id' => $member->id,
            'role' => 3,
        ]);

        /** @var CommunityRepository $repository */
        $repository = app(CommunityRepository::class);

        $this->assertFalse($repository->changeMembership($community, $owner, 0));
        $this->assertSame(1, CommunityRole::query()
            ->where('community_id', $community->id)
            ->where('user_id', $owner->id)
            ->value('role'));

        $this->assertTrue($repository->changeMembership($community, $member, 0));
        $this->assertDatabaseMissing('community_roles', [
            'community_id' => $community->id,
            'user_id' => $member->id,
        ]);
    }

    private function community(string $type, CommunityStatus $status, string $name): Community
    {
        /** @var Community $community */
        $community = Community::query()->create([
            'type' => $type,
            'status' => $status->value,
            'name' => $name,
            'about' => 'Описание',
            'avatar' => '',
            'cover_page' => '',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
        ]);

        return $community;
    }

    private function user(int $id): User
    {
        $user = new User([
            'firstname' => 'User',
            'lastname' => (string) $id,
            'email' => 'user' . $id . '@example.test',
            'status' => 1,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
