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

            $mock->shouldReceive('myGroups')->with($viewer->id, 5, 0)->andReturn(collect());
            $mock->shouldReceive('popularGroups')->with(5, 0)->andReturn(collect([$group]));
            $mock->shouldReceive('invitedGroups')->with($viewer->id, 5, 0)->andReturn(collect());
            $mock->shouldReceive('myGroupsCount')->with($viewer->id)->andReturn(0);
            $mock->shouldReceive('popularGroupsCount')->andReturn(1);
            $mock->shouldReceive('invitedGroupsCount')->with($viewer->id)->andReturn(0);
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

    public function test_groups_popular_ajax_returns_cards(): void
    {
        $viewer = $this->user(1);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer): void {
            $group = [
                'id' => 12,
                'name' => 'Беговой клуб',
                'type_label' => 'Открытая группа',
                'sport_type' => 'Бег',
                'status' => '',
                'place' => 'Москва',
                'members_text' => '6 участников',
                'avatar' => 'http://site3.local/uploads/images/groupcontent/avatar/group.jpg',
                'can_edit' => false,
            ];

            $mock->shouldReceive('popularGroups')->with(5, 5)->andReturn(collect([$group]));
            $mock->shouldReceive('popularGroups')->with(1, 10)->andReturn(collect());
            $mock->shouldReceive('role')->with(12, $viewer->id)->andReturn(null);
            $mock->shouldReceive('roleLabel')->with(null)->andReturn('');
        });

        $response = $this->get('/ajax/get_pop_communities_list?number=5&offset=5&type=group')
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('has_more', false)
            ->assertJsonFragment(['count' => 1]);

        $this->assertStringContainsString('Беговой клуб', $response->json('html'));
    }

    public function test_group_and_team_cards_use_legacy_noimage_fallback(): void
    {
        $community = [
            'id' => 7,
            'name' => 'Упражнения для мужчин',
            'type_label' => 'Открытая группа',
            'sport_type' => 'Здоровый образ жизни',
            'status' => '',
            'place' => 'Москва',
            'members_text' => '0 участников',
            'avatar' => '',
            'can_edit' => false,
        ];

        $groupHtml = view('front.groups._group-card', ['group' => $community])->render();
        $teamHtml = view('front.teams._team-card', ['team' => $community])->render();

        $this->assertStringContainsString('frontend/images/noimage.png', $groupHtml);
        $this->assertStringContainsString('frontend/images/noimage.png', $teamHtml);
        $this->assertStringNotContainsString('frontend/images/default_group.png', $groupHtml);
        $this->assertStringNotContainsString('frontend/images/default_group.png', $teamHtml);
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
