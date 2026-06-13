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

class TeamsPageTest extends TestCase
{
    public function test_teams_page_renders_search_filters_and_scroll_state(): void
    {
        $viewer = $this->user(1);
        $filters = [
            'place' => 'Москва',
            'sport' => 'Футбол',
            'search' => 'Play',
            'id_place' => 44,
            'id_sport' => 7,
        ];

        $popularTeam = $this->team(18, 'PlayToGet');
        $myTeam = $this->team(19, 'Моя команда');
        $invitedTeam = $this->team(20, 'Приглашение');

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $filters, $popularTeam, $myTeam, $invitedTeam): void {
            $mock->shouldReceive('myTeams')->with($viewer->id, 5, 0, $filters)->andReturn(collect([$myTeam]));
            $mock->shouldReceive('popularTeams')->with(5, 0, $filters)->andReturn(collect([$popularTeam]));
            $mock->shouldReceive('invitedTeams')->with($viewer->id, 5, 0, $filters)->andReturn(collect([$invitedTeam]));
            $mock->shouldReceive('myTeamsCount')->with($viewer->id, $filters)->andReturn(6);
            $mock->shouldReceive('popularTeamsCount')->with($filters)->andReturn(6);
            $mock->shouldReceive('invitedTeamsCount')->with($viewer->id, $filters)->andReturn(2);
            $mock->shouldReceive('role')->andReturn(null);
            $mock->shouldReceive('roleLabel')->with(null)->andReturn('');
        });

        $this->get('/teams?' . http_build_query($filters))
            ->assertStatus(200)
            ->assertSee('Ищу команду в городе')
            ->assertSee('Ищу свой спорт')
            ->assertSee('Ключевое слово')
            ->assertSee('value="Москва"', false)
            ->assertSee('value="Футбол"', false)
            ->assertSee('value="Play"', false)
            ->assertSee('value="44"', false)
            ->assertSee('value="7"', false)
            ->assertSee('Популярные команды')
            ->assertSee('Мои команды')
            ->assertSee('Меня пригласили')
            ->assertSee('id="pop_team_list"', false)
            ->assertSee('id="my_team_list"', false)
            ->assertSee('id="invited_team_list"', false)
            ->assertSee('data-feed="popular"', false)
            ->assertSee('data-feed="mygroups"', false)
            ->assertSee('data-feed="invited"', false)
            ->assertSee('get_pop_communities_list', false)
            ->assertSee('get_communities_list', false);
    }

    public function test_teams_invited_ajax_uses_filters(): void
    {
        $viewer = $this->user(1);
        $filters = [
            'place' => 'Москва',
            'sport' => 'Футбол',
            'search' => 'Play',
            'id_place' => 0,
            'id_sport' => 0,
        ];
        $team = $this->team(21, 'PlayToGet приглашает');

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $filters, $team): void {
            $mock->shouldReceive('invitedTeams')->with($viewer->id, 5, 0, $filters)->andReturn(collect([$team]));
            $mock->shouldReceive('invitedTeams')->with($viewer->id, 1, 5, $filters)->andReturn(collect());
            $mock->shouldReceive('role')->with(21, $viewer->id)->andReturn(5);
            $mock->shouldReceive('roleLabel')->with(5)->andReturn('Приглашен');
        });

        $response = $this->get('/ajax/get_communities_list?' . http_build_query([
            'number' => 5,
            'offset' => 0,
            'type' => 'team',
            'feed' => 'invited',
            'place' => 'Москва',
            'sport' => 'Футбол',
            'search' => 'Play',
        ]))
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('has_more', false)
            ->assertJsonFragment(['count' => 1]);

        $this->assertStringContainsString('PlayToGet приглашает', $response->json('html'));
        $this->assertStringContainsString('Приглашен', $response->json('html'));
    }

    public function test_team_create_page_renders_legacy_form_controls(): void
    {
        $this->actingAs($this->user(1), 'web');

        $this->get('/teams/create')
            ->assertOk()
            ->assertSee('Создание команды')
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
            ->assertSee('class="file_upload team-file-upload"', false)
            ->assertSee('name="avatar_file"', false)
            ->assertSee('name="cover_file"', false)
            ->assertSee('Загрузить аватар')
            ->assertSee('Загрузить обложку');
    }

    public function test_team_edit_page_renders_tabs_and_top_actions(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);
        $settings = $this->settings(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team, $settings): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('serializeTeam')->with($team)->andReturn($this->teamData(18, 'выпы'));
            $mock->shouldReceive('permissions')->with($team, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(18, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($team, $viewer)->andReturn('owner');
            $mock->shouldReceive('canInvite')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('settings')->with($team)->andReturn($settings);
            $mock->shouldReceive('admins')->with(18)->andReturn(collect());
            $mock->shouldReceive('blocked')->with(18)->andReturn(collect());
        });

        $this->get('/teams/18/edit')
            ->assertOk()
            ->assertSee('Редактирование команды')
            ->assertSee('Пригласить друзей')
            ->assertDontSee('class="groups_button_leave js-team-leave" data-community-id="18"', false)
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

    public function test_team_show_page_renders_feed_wall(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);
        $comment = $this->comment(100, 'Запись команды');

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('serializeTeam')->with($team)->andReturn($this->teamData(18, 'выпы'));
            $mock->shouldReceive('permissions')->with($team, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(18, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($team, $viewer)->andReturn('owner');
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('canInvite')->with($team, $viewer)->andReturn(true);
        });

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer, $comment): void {
            $mock->shouldReceive('comments')->with('team', 18, 10, 0, $viewer)->andReturn(collect([$comment]));
            $mock->shouldReceive('hasMoreComments')->with('team', 18, 10, 0)->andReturn(false);
        });

        $this->get('/teams/18')
            ->assertOk()
            ->assertSee('value="team"', false)
            ->assertSee('data-commentable-type="team"', false)
            ->assertSee('Что у Вас интересного?')
            ->assertSee('подпись')
            ->assertSee('Запись команды')
            ->assertSee('active-link', false)
            ->assertDontSee('class="groups_button_leave js-team-leave" data-community-id="18"', false)
            ->assertSee('Лента');
    }

    public function test_team_members_page_still_renders_members(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);
        $member = [
            'id' => 2,
            'name' => 'Дмитрий Панкратов',
            'firstname' => 'Дмитрий',
            'lastname' => 'Панкратов',
            'avatar' => 'http://site3.local/frontend/images/noimage.png',
            'city' => 'Москва',
            'role' => 3,
            'role_name' => 'Участник',
            'is_online' => false,
        ];

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team, $member): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('serializeTeam')->with($team)->andReturn($this->teamData(18, 'выпы'));
            $mock->shouldReceive('permissions')->with($team, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(18, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($team, $viewer)->andReturn('owner');
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('canInvite')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('members')->with(18)->andReturn(collect([$member]));
            $mock->shouldReceive('applications')->with(18)->andReturn(collect());
        });

        $this->get('/teams/18/members')
            ->assertOk()
            ->assertSee('Участники')
            ->assertSee('Дмитрий')
            ->assertSee('Панкратов')
            ->assertDontSee('data-commentable-type="team"', false);
    }

    public function test_team_events_page_renders_legacy_search_form_for_managers(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('serializeTeam')->with($team)->andReturn($this->teamData(18, 'выпы'));
            $mock->shouldReceive('permissions')->with($team, $viewer)->andReturn(['wall' => true, 'photo' => true, 'video' => true]);
            $mock->shouldReceive('role')->with(18, $viewer->id)->andReturn(1);
            $mock->shouldReceive('membershipType')->with($team, $viewer)->andReturn('owner');
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('canInvite')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('events')->with(18)->andReturn(collect([$this->eventData(7, 'Командный турнир')]));
        });

        $this->get('/teams/18/events')
            ->assertOk()
            ->assertSee('Поиск')
            ->assertSee('class="form-control search_events"', false)
            ->assertSee('placeholder="Начните вводить"', false)
            ->assertSee('id="resultSearch"', false)
            ->assertSee('search_event', false)
            ->assertSee('change_event_community_status', false)
            ->assertSee('Мероприятия команды')
            ->assertSee('Командный турнир');
    }

    public function test_team_membership_ajax_changes_status(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('changeMembership')->with($team, $viewer, 0)->andReturn(true);
            $mock->shouldReceive('membershipType')->with($team, $viewer)->andReturn('none');
        });

        $this->post('/ajax/change_member_status', [
            'id' => 18,
            'status' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('result', 'success')
            ->assertJsonPath('member', 'none');
    }

    public function test_team_owner_membership_ajax_cannot_leave(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('changeMembership')->with($team, $viewer, 0)->andReturn(false);
        });

        $this->post('/ajax/change_member_status', [
            'id' => 18,
            'status' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('result', 'error')
            ->assertJsonPath('member', null);
    }

    public function test_team_event_search_ajax_uses_repository(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);
        $event = $this->eventData(7, 'Весенний турнир');

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team, $event): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('searchEventsForTeam')->with(18, 'турнир', 10, 0, [
                'place' => '',
                'sport' => '',
            ])->andReturn(collect([$event]));
        });

        $response = $this->post('/ajax/search_event', [
            'number' => 10,
            'offset' => 0,
            'member_id' => 18,
            'eventable_type' => 'team',
            'search' => 'турнир',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('count', 1);

        $this->assertStringContainsString('Весенний турнир', $response->json('html'));
        $this->assertStringContainsString('class="addEvent"', $response->json('html'));
        $this->assertStringContainsString('data-item="7"', $response->json('html'));
    }

    public function test_team_event_membership_ajax_uses_repository(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('canManage')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('changeEventMembership')->with($team, 7, 1)->andReturn(true);
        });

        $this->post('/ajax/change_event_community_status', [
            'event_id' => 7,
            'community_id' => 18,
            'status' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('result', 'success');
    }

    public function test_team_invitation_ajax_invites_friends(): void
    {
        $viewer = $this->user(1);
        $team = $this->community(18);

        $this->actingAs($viewer, 'web');

        $this->mock(CommunityRepository::class, function (MockInterface $mock) use ($viewer, $team): void {
            $mock->shouldReceive('findTeam')->with(18)->andReturn($team);
            $mock->shouldReceive('canInvite')->with($team, $viewer)->andReturn(true);
            $mock->shouldReceive('inviteFriends')->with($team, $viewer)->andReturn(4);
        });

        $this->post('/ajax/send_community_invitation', [
            'community_id' => 18,
        ])
            ->assertOk()
            ->assertJsonPath('result', 'success')
            ->assertJsonPath('count', 4);
    }

    private function team(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'type_label' => 'Открытая команда',
            'sport_type' => 'Футбол',
            'status' => '',
            'place' => 'Москва',
            'members_text' => '5 участников',
            'avatar' => 'http://site3.local/uploads/images/teamcontent/avatar/team.jpg',
            'can_edit' => false,
        ];
    }

    private function community(int $id): Community
    {
        $team = new Community([
            'type' => 'team',
            'name' => 'выпы',
            'about' => 'Описание',
            'place' => 'Моготуй',
            'sport_type' => 'Радиоспорт',
            'avatar' => '',
            'cover_page' => '',
            'status' => CommunityStatus::Confirmed->value,
        ]);
        $team->id = $id;
        $team->exists = true;

        return $team;
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

    private function teamData(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'about' => 'Описание',
            'place' => 'Моготуй',
            'sport_type' => 'Радиоспорт',
            'type_label' => 'Открытая команда',
            'avatar' => 'http://site3.local/frontend/images/default_group.png',
            'cover' => 'http://site3.local/frontend/images/default_group.png',
            'members_count' => 1,
            'members_text' => '1 участников',
        ];
    }

    private function eventData(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'avatar' => 'http://site3.local/frontend/images/noimage.png',
            'sport_type' => 'Футбол',
            'city' => 'Москва',
            'date' => '08.06.2026 12:00',
            'date_to' => '08.06.2026 14:00',
            'description' => 'Описание мероприятия',
            'participants' => 2,
            'user_participants' => 3,
            'active' => true,
        ];
    }

    private function comment(int $id, string $content): array
    {
        return [
            'id' => $id,
            'parent_id' => 0,
            'content_id' => 18,
            'author_id' => 1,
            'author_name' => 'Александр Яницкий',
            'author_url' => 'http://site3.local/profile/1',
            'avatar' => 'http://site3.local/frontend/images/noimage.png',
            'created' => '08.06.2026 02:30',
            'content' => $content,
            'attachments' => collect(),
            'likes_count' => 0,
            'shares_count' => 0,
            'can_interact' => true,
            'can_share' => false,
            'can_delete' => true,
            'replies' => collect(),
        ];
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
}
