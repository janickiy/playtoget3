<?php

namespace Tests\Feature;

use App\Enums\CommunityStatus;
use App\Models\Community;
use App\Models\CommunitySetting;
use App\Models\User;
use App\Repositories\CommunityRepository;
use App\Repositories\ProfileRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class GroupsPageTest extends TestCase
{
    public function test_groups_page_renders_legacy_sections(): void
    {
        $viewer = $this->user(1);

        $this->actingAs($viewer, 'web');
        $filters = ['place' => '', 'sport' => '', 'search' => '', 'id_place' => 0, 'id_sport' => 0];

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $filters): void {
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

            $mock->shouldReceive('myGroups')->with($viewer->id, 5, 0, $filters)->andReturn(collect());
            $mock->shouldReceive('popularGroups')->with(5, 0, $filters)->andReturn(collect([$group]));
            $mock->shouldReceive('invitedGroups')->with($viewer->id, 5, 0, $filters)->andReturn(collect());
            $mock->shouldReceive('myGroupsCount')->with($viewer->id, $filters)->andReturn(0);
            $mock->shouldReceive('popularGroupsCount')->with($filters)->andReturn(1);
            $mock->shouldReceive('invitedGroupsCount')->with($viewer->id, $filters)->andReturn(0);
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
        $filters = ['place' => '', 'sport' => '', 'search' => '', 'id_place' => 0, 'id_sport' => 0];

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $filters): void {
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

            $mock->shouldReceive('popularGroups')->with(5, 5, $filters)->andReturn(collect([$group]));
            $mock->shouldReceive('popularGroups')->with(1, 10, $filters)->andReturn(collect());
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

    public function test_group_show_renders_feed_like_team_page(): void
    {
        $viewer = $this->user(1);
        $group = new Community([
            'type' => 'group',
            'name' => 'Лига Чемпионов',
            'about' => 'Футбольная группа',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
        ]);
        $group->id = 2;
        $group->exists = true;

        $this->actingAs($viewer, 'web');

        $groupData = [
            'id' => 2,
            'name' => 'Лига Чемпионов',
            'about' => 'Футбольная группа',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
            'avatar' => 'http://site3.local/frontend/images/noimage.png',
            'cover' => 'http://site3.local/frontend/images/default_group.png',
        ];

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $group, $groupData): void {
            $mock->shouldReceive('findGroup')->with(2)->andReturn($group);
            $mock->shouldReceive('serializeGroup')->with($group)->andReturn($groupData);
            $mock->shouldReceive('permissions')->with($group, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(2, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($group, $viewer)->andReturn('owner');
            $mock->shouldReceive('canManage')->with($group, $viewer)->andReturn(true);
            $mock->shouldReceive('canInvite')->with($group, $viewer)->andReturn(true);
        });
        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('comments')->with('group', 2, 10, 0, $viewer)->andReturn(collect());
            $mock->shouldReceive('hasMoreComments')->with('group', 2, 10, 0)->andReturn(false);
        });

        $this->get('/groups/2')
            ->assertOk()
            ->assertSee('Лига Чемпионов')
            ->assertDontSee('class="groups_button_leave js-group-leave" data-community-id="2"', false)
            ->assertSee('Лента')
            ->assertSee('Фотографии')
            ->assertSee('Видео')
            ->assertSee('Мероприятия')
            ->assertSee('commentable_type" value="group', false);
    }

    public function test_group_create_page_renders_form_without_profile_top_and_tabs(): void
    {
        $this->actingAs($this->user(1), 'web');

        $this->get('/groups/create')
            ->assertOk()
            ->assertSee('Создание группы')
            ->assertSee('class="form-horizontal create_form"', false)
            ->assertDontSee('top_thumb_avatar', false)
            ->assertDontSee('community-form-tabs', false)
            ->assertDontSee('href="#info"', false)
            ->assertDontSee('frontend/css/jquery-ui-1.8.16.custom.css', false)
            ->assertDontSee('frontend/js/jquery-ui.min.js', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="about"', false)
            ->assertSee('name="place"', false)
            ->assertSee('name="sport"', false)
            ->assertSee('frontend/js/search.js', false)
            ->assertSee('data-type="search_city"', false)
            ->assertSee('data-type="search_sport"', false)
            ->assertSee('class="select-place"', false)
            ->assertSee('id="preview_ava"', false)
            ->assertSee('id="preview_cover"', false)
            ->assertSee('Загрузить аватар')
            ->assertSee('Загрузить обложку');
    }

    public function test_group_edit_page_renders_tabs_and_top_actions(): void
    {
        $viewer = $this->user(1);
        $group = $this->community(2);
        $settings = $this->settings(2);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $group, $settings): void {
            $mock->shouldReceive('findGroup')->with(2)->andReturn($group);
            $mock->shouldReceive('canManage')->with($group, $viewer)->andReturn(true);
            $mock->shouldReceive('serializeGroup')->with($group)->andReturn($this->groupData(2, 'Лига Чемпионов'));
            $mock->shouldReceive('permissions')->with($group, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(2, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($group, $viewer)->andReturn('owner');
            $mock->shouldReceive('canInvite')->with($group, $viewer)->andReturn(true);
            $mock->shouldReceive('settings')->with($group)->andReturn($settings);
            $mock->shouldReceive('admins')->with(2)->andReturn(collect());
            $mock->shouldReceive('blocked')->with(2)->andReturn(collect());
        });

        $this->get('/groups/2/edit')
            ->assertOk()
            ->assertSee('Редактирование группы')
            ->assertSee('Пригласить друзей')
            ->assertDontSee('class="groups_button_leave js-group-leave" data-community-id="2"', false)
            ->assertSee('Администраторы')
            ->assertSee('Приватность')
            ->assertSee('Черный список')
            ->assertSee('community-form-tabs', false)
            ->assertSee('frontend/css/jquery-ui-1.8.16.custom.css', false)
            ->assertSee('frontend/js/jquery-ui.min.js', false)
            ->assertSee('$tab.tabs({active: 0});', false)
            ->assertSee('background: #40aaa1', false)
            ->assertSee('name="community[permission_wall]"', false)
            ->assertSee('name="community[type]"', false)
            ->assertSee('Загрузить аватар')
            ->assertSee('Загрузить обложку');
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
            'status' => 1,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }

    private function community(int $id): Community
    {
        $group = new Community([
            'type' => 'group',
            'name' => 'Лига Чемпионов',
            'about' => 'Футбольная группа',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
            'avatar' => '',
            'cover_page' => '',
            'status' => CommunityStatus::Confirmed->value,
        ]);
        $group->id = $id;
        $group->exists = true;

        return $group;
    }

    private function settings(int $communityId): CommunitySetting
    {
        $settings = new CommunitySetting([
            'community_id' => $communityId,
            'permission_wall' => 0,
            'permission_photo' => 0,
            'permission_video' => 0,
            'type' => 0,
        ]);
        $settings->exists = true;

        return $settings;
    }

    private function groupData(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'about' => 'Футбольная группа',
            'place' => 'Москва',
            'sport_type' => 'Футбол',
            'type_label' => 'Открытая группа',
            'avatar' => 'http://site3.local/frontend/images/default_group.png',
            'cover' => 'http://site3.local/frontend/images/default_group.png',
            'members_count' => 1,
            'members_text' => '1 участников',
        ];
    }
}
