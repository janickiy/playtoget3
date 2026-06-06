<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    public function test_profile_page_renders_legacy_wall_layout(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $profile = $this->user(2, 'Дмитрий', 'Панкратов');
        $profile->secondname = 'ebgik';

        $this->actingAs($viewer, 'web');

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->with(1, 2)->andReturn('friend');
        });

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($profile, $viewer): void {
            $mock->shouldReceive('profile')->with(2)->andReturn($profile);
            $mock->shouldReceive('profileData')->with($profile)->andReturn([
                'avatar' => 'http://site3.local/uploads/images/user/avatar/profile.jpg',
                'cover' => 'http://site3.local/templates/images/content-bg.png',
                'firstname' => 'Дмитрий',
                'lastname' => 'Панкратов',
                'secondname' => 'ebgik',
                'about' => '',
                'last_visit' => '18 апреля 2016 в 00:52',
                'birthday' => '16 июня 1991',
                'city' => 'Москва',
                'phone' => '+7 (966) 105-52-72',
                'contact_email' => '',
                'skype' => '',
                'website' => '',
                'about_sport' => '',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect([['name' => 'Playtoget', 'description' => 'Web-developer', 'period' => '']]),
            ]);
            $mock->shouldReceive('permissions')->with($profile, $viewer, 'friend')->andReturn([
                'send_message' => true,
                'wall' => true,
                'photo' => true,
                'video' => true,
                'friends' => true,
                'teams' => true,
            ]);
            $mock->shouldReceive('wallComments')->with(2, 10, 0, $viewer)->andReturn(collect([
                [
                    'id' => 296,
                    'parent_id' => 0,
                    'content_id' => 2,
                    'author_id' => 2,
                    'author_name' => 'Дмитрий Панкратов',
                    'author_url' => 'http://site3.local/profile/2',
                    'avatar' => 'http://site3.local/uploads/images/user/avatar/profile.jpg',
                    'created' => '18.04.2016 00:52',
                    'content' => 'Real',
                    'attachments' => collect(),
                    'likes_count' => 0,
                    'shares_count' => 0,
                    'can_share' => true,
                    'can_delete' => false,
                    'replies' => collect(),
                ],
            ]));
            $mock->shouldReceive('hasMoreWallComments')->with(2, 10, 0)->andReturn(false);
        });

        $this->get('/profile/2')
            ->assertStatus(200)
            ->assertSee('Дмитрий')
            ->assertSee('Панкратов')
            ->assertSee('(ebgik)')
            ->assertSee('Написать <span>сообщение</span>', false)
            ->assertSee('Удалить<span> друга</span>', false)
            ->assertSee('Заблокировать')
            ->assertSee('Был(a) на сайте')
            ->assertSee('18 апреля 2016 в 00:52')
            ->assertSee('Playtoget')
            ->assertDontSee('Web-developer')
            ->assertSee('id="addCommentForm"', false)
            ->assertSee('id="message-296"', false)
            ->assertSee('Real')
            ->assertSee('/ajax/getcomments', false)
            ->assertSee('templates/js/profile.js', false);
    }

    public function test_guest_profile_wall_hides_interactive_legacy_actions(): void
    {
        $profile = $this->user(2, 'Дмитрий', 'Панкратов');

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->with(null, 2)->andReturn('nofriend');
        });

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($profile): void {
            $mock->shouldReceive('profile')->with(2)->andReturn($profile);
            $mock->shouldReceive('profileData')->with($profile)->andReturn([
                'avatar' => 'http://site3.local/uploads/images/user/avatar/profile.jpg',
                'cover' => 'http://site3.local/uploads/images/user/cover_page/2_923f6633336d81315d03f530022d2082.jpg',
                'firstname' => 'Дмитрий',
                'lastname' => 'Панкратов',
                'secondname' => '',
                'about' => '',
                'last_visit' => '31 июля 2019 в 18:07',
                'birthday' => '',
                'city' => '',
                'phone' => '',
                'contact_email' => '',
                'skype' => '',
                'website' => '',
                'about_sport' => '',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect(),
            ]);
            $mock->shouldReceive('permissions')->with($profile, null, 'nofriend')->andReturn([
                'send_message' => false,
                'wall' => true,
                'photo' => true,
                'video' => true,
                'friends' => true,
                'teams' => false,
            ]);
            $mock->shouldReceive('wallComments')->with(2, 10, 0, null)->andReturn(collect([
                [
                    'id' => 472,
                    'parent_id' => 0,
                    'content_id' => 2,
                    'author_id' => 1,
                    'author_name' => 'Александр Яницкий',
                    'author_url' => 'http://site3.local/profile/1',
                    'avatar' => 'http://site3.local/uploads/images/user/avatar/owner.jpg',
                    'created' => '06.06.2026 16:56',
                    'content' => '',
                    'attachments' => collect([
                        ['photo_id' => 935, 'url' => 'http://site3.local/uploads/images/photogallery/user_attach/s_photo.jpg'],
                    ]),
                    'likes_count' => 0,
                    'shares_count' => 0,
                    'can_interact' => false,
                    'can_share' => false,
                    'can_delete' => false,
                    'replies' => collect(),
                ],
            ]));
            $mock->shouldReceive('hasMoreWallComments')->with(2, 10, 0)->andReturn(false);
        });

        $this->get('/profile/2')
            ->assertStatus(200)
            ->assertSee('31 июля 2019 в 18:07')
            ->assertSee('id="message-472"', false)
            ->assertSee('class="photo_big"', false)
            ->assertSee('Чтобы оставить комментарий авторизуйтесь')
            ->assertDontSee('id="reply-472"', false)
            ->assertDontSee('id="like-comment-472"', false)
            ->assertDontSee('teams/user/2', false);
    }

    private function user(int $id, string $firstname, string $lastname): User
    {
        $user = new User([
            'firstname' => $firstname,
            'lastname' => $lastname,
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
