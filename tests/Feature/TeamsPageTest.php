<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\CommunityRepository;
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
            ->assertSee('Искать команду в городе')
            ->assertSee('Искать вид спорта')
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
