<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\CommunityRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class GroupsPageTest extends TestCase
{
    public function test_groups_page_renders_legacy_sections(): void
    {
        $viewer = $this->user(1);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer): void {
            $group = [
                'id' => 9,
                'name' => 'Лига Чемпионов УЕФА',
                'type_label' => 'Открытая группа',
                'sport_type' => 'Футбол',
                'status' => '',
                'place' => 'Москва',
                'members_text' => '3 участников',
                'avatar' => 'http://site3.local/uploads/images/groupcontent/avatar/group.jpg',
                'can_edit' => false,
            ];

            $mock->shouldReceive('myGroups')->with($viewer->id)->andReturn(collect());
            $mock->shouldReceive('popularGroups')->andReturn(collect([$group]));
            $mock->shouldReceive('invitedGroups')->with($viewer->id)->andReturn(collect());
            $mock->shouldReceive('role')->andReturn(null);
            $mock->shouldReceive('roleLabel')->andReturn('');
        });

        $this->get('/groups')
            ->assertStatus(200)
            ->assertSee('Популярные группы')
            ->assertSee('Мои группы')
            ->assertSee('Меня пригласили')
            ->assertSee('Создать группу')
            ->assertSee('Лига Чемпионов УЕФА')
            ->assertSee('/groups/create', false)
            ->assertSee('/groups/9', false);
    }

    private function user(int $id): User
    {
        $user = new User([
            'firstname' => 'Александр',
            'lastname' => 'Яницкий',
            'email' => 'user' . $id . '@example.test',
            'sex' => 'male',
            'confirmed' => true,
            'banned' => false,
            'deleted' => false,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
