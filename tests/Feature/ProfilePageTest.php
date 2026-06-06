<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    public function test_profile_edit_page_renders_legacy_settings_tabs(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $settings = new UserSetting([
            'permission_send_message' => 1,
            'permission_view_profile' => 0,
            'permission_view_friends' => 0,
            'permission_view_photo' => 0,
            'permission_view_video' => 0,
            'permission_view_wall' => 0,
            'permission_comment_photo' => 0,
            'permission_comment_video' => 0,
            'permission_comment_wall' => 0,
            'notification_friends_request' => 'yes',
            'notification_private_messages' => 'no',
            'notification_wall_comments' => 'yes',
            'notification_picture_comments' => 'yes',
            'notification_video_comments' => 'yes',
            'notification_answers_in_comments' => 'yes',
            'notification_events' => 'yes',
            'notification_birthdays' => 'yes',
        ]);

        $this->actingAs($viewer, 'web');

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer, $settings): void {
            $mock->shouldReceive('topProfileData')->with($viewer)->andReturn([
                'user' => $viewer,
                'avatar' => 'http://site3.local/uploads/images/user/avatar/1.jpg',
                'cover' => 'http://site3.local/uploads/images/user/cover_page/1.jpg',
                'firstname' => 'Александр',
                'lastname' => 'Яницкий',
                'about' => '',
            ]);
            $mock->shouldReceive('profileSettings')->with($viewer)->andReturn($settings);
            $mock->shouldReceive('blockedUsers')->with($viewer)->andReturn(collect([
                [
                    'id' => 2,
                    'name' => 'Дмитрий Панкратов',
                    'avatar' => 'http://site3.local/uploads/images/user/avatar/2.jpg',
                    'url' => 'http://site3.local/profile/2',
                ],
            ]));
            $mock->shouldReceive('securityLogs')->with($viewer)->andReturn(collect([
                ['ip' => '127.0.0.1', 'os' => 'macOS', 'browser' => 'Safari', 'time' => '06.06.2026 12:00'],
            ]));
            $mock->shouldReceive('permissionFields')->andReturn([
                'permission_send_message' => 'Кто может писать мне сообщения',
            ]);
            $mock->shouldReceive('notificationFields')->andReturn([
                'notification_friends_request' => 'Заявки в друзья',
                'notification_private_messages' => 'Личные сообщения',
            ]);
        });

        $this->get('/profile/edit')
            ->assertStatus(200)
            ->assertSee('id="profile-settings-form"', false)
            ->assertSee('id="profile-avatar-input"', false)
            ->assertSee('id="profile-avatar-file"', false)
            ->assertSee('id="avatar-crop-modal"', false)
            ->assertSee('id="profile-cover-input"', false)
            ->assertSee('id="preview_ava"', false)
            ->assertSee('id="preview_cover"', false)
            ->assertSee('Контакт')
            ->assertSee('Приватность')
            ->assertSee('Оповещения')
            ->assertSee('Безопасность')
            ->assertSee('Черный список')
            ->assertSee('Кто может писать мне сообщения')
            ->assertSee('Заявки в друзья')
            ->assertSee('Дмитрий Панкратов')
            ->assertSee('frontend/css/jquery.Jcrop.css', false)
            ->assertSee('frontend/js/jquery.Jcrop.min.js', false)
            ->assertSee('frontend/js/profile-settings.js', false);
    }

    public function test_profile_edit_post_passes_settings_to_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');

        $this->actingAs($viewer, 'web');

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('updateProfileSettings')
                ->once()
                ->withArgs(fn (User $user, array $input, mixed $temporaryAvatar, mixed $cover): bool => $user === $viewer
                    && $input['contact_email'] === 'new@example.test'
                    && (int) $input['permission_send_message'] === 1
                    && $input['notification_friends_request'] === 'yes'
                    && $temporaryAvatar === '1_cropped.jpg'
                    && $cover === null);
        });

        $this->post('/profile/edit', [
            'user' => [
                'contact_email' => 'new@example.test',
                'phone' => '+7 999 000-00-00',
                'skype' => 'alex',
                'website' => 'https://example.test',
                'permission_send_message' => 1,
                'permission_view_profile' => 0,
                'permission_view_friends' => 0,
                'permission_view_photo' => 0,
                'permission_view_video' => 0,
                'permission_view_wall' => 0,
                'permission_comment_photo' => 0,
                'permission_comment_video' => 0,
                'permission_comment_wall' => 0,
                'notification_friends_request' => 'yes',
            ],
            'file_ava' => '1_cropped.jpg',
        ])
            ->assertRedirect('/profile/edit')
            ->assertSessionHas('status', 'Изменения сохранены');
    }

    public function test_profile_avatar_upload_ajax_returns_cropped_temporary_file(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 500);

        $this->actingAs($viewer, 'web');

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('cropTemporaryAvatar')
                ->once()
                ->withArgs(fn (User $user, UploadedFile $file, array $crop): bool => $user === $viewer
                    && $file->getClientOriginalName() === 'avatar.jpg'
                    && (int) $crop['x'] === 10
                    && (int) $crop['y'] === 20
                    && (int) $crop['w'] === 300
                    && (int) $crop['h'] === 300)
                ->andReturn([
                    'file' => '1_cropped.jpg',
                    'url' => 'http://site3.local/uploads/images/tmp/profile/avatar/1_cropped.jpg',
                ]);
        });

        $this->post('/ajax/uploadavatar', [
            'avatar' => $file,
            'x' => 10,
            'y' => 20,
            'w' => 300,
            'h' => 300,
        ])
            ->assertStatus(200)
            ->assertJson([
                'result' => 'success',
                'file' => '1_cropped.jpg',
                'url' => 'http://site3.local/uploads/images/tmp/profile/avatar/1_cropped.jpg',
            ]);
    }

    public function test_profile_repository_crops_avatar_to_square_temporary_image(): void
    {
        Storage::fake('public');

        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $repository = new ProfileRepository(new User());
        $result = $repository->cropTemporaryAvatar($viewer, UploadedFile::fake()->image('avatar.png', 600, 500), [
            'x' => 30,
            'y' => 40,
            'w' => 300,
            'h' => 300,
        ]);
        $path = 'images/tmp/profile/avatar/' . $result['file'];
        $temporaryFile = tempnam(sys_get_temp_dir(), 'avatar-crop-');

        Storage::disk('public')->assertExists($path);
        file_put_contents($temporaryFile, Storage::disk('public')->get($path));

        [$width, $height] = getimagesize($temporaryFile);
        unlink($temporaryFile);

        $this->assertSame(300, $width);
        $this->assertSame(300, $height);
    }

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
                'cover' => 'http://site3.local/frontend/images/content-bg.png',
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
            ->assertSee('/profile/1/messages/user/2', false)
            ->assertSee('Удалить<span> друга</span>', false)
            ->assertSee('id="remove_friend" data-item="2"', false)
            ->assertSee('Заблокировать')
            ->assertSee('id="block_user" data-item="2"', false)
            ->assertSee('Был(a) на сайте')
            ->assertSee('18 апреля 2016 в 00:52')
            ->assertSee('Playtoget')
            ->assertDontSee('Web-developer')
            ->assertSee('id="addCommentForm"', false)
            ->assertSee('id="message-296"', false)
            ->assertSee('Real')
            ->assertSee('/ajax/getcomments', false)
            ->assertSee('<input id="submit" type="submit">', false)
            ->assertSee('<div class="link_attach" data-num="2"></div>', false)
            ->assertSee('<div class="files_block" data-num="2"></div>', false)
            ->assertDontSee('class="send" value="Отправить"', false)
            ->assertSee('frontend/js/profile.js', false);
    }

    public function test_wall_attachment_script_uses_laravel_ajax_endpoint(): void
    {
        $script = file_get_contents(public_path('frontend/js/emotions.js'));
        $commonScript = file_get_contents(public_path('frontend/js/script_all.js'));

        $this->assertStringContainsString("formData.append('_token', token);", $script);
        $this->assertStringContainsString("url: '/ajax/add_photo_ajax_attach'", $script);
        $this->assertStringNotContainsString('/?task=ajax_action&action=add_photo_ajax_attach', $script);
        $this->assertStringContainsString("url: '/ajax/removecomment'", $commonScript);
        $this->assertStringNotContainsString('/?task=ajax_action&action=removecomment', $commonScript);
    }

    public function test_own_profile_hides_external_profile_links(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');

        $this->actingAs($viewer, 'web');

        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->with(1, 1)->andReturn('');
        });

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('profile')->with(1)->andReturn($viewer);
            $mock->shouldReceive('profileData')->with($viewer)->andReturn([
                'avatar' => 'http://site3.local/uploads/images/user/avatar/owner.jpg',
                'cover' => 'http://site3.local/uploads/images/user/cover_page/owner.jpg',
                'firstname' => 'Александр',
                'lastname' => 'Яницкий',
                'secondname' => 'Yanack',
                'about' => '',
                'last_visit' => '5 июня 2026 в 00:06',
                'birthday' => '',
                'city' => '',
                'phone' => '',
                'contact_email' => '',
                'skype' => '',
                'website' => '',
                'about_sport' => 'gh',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect(),
            ]);
            $mock->shouldReceive('permissions')->with($viewer, $viewer, '')->andReturn([
                'send_message' => false,
                'wall' => true,
                'photo' => true,
                'video' => true,
                'friends' => true,
                'teams' => true,
            ]);
            $mock->shouldReceive('wallComments')->with(1, 10, 0, $viewer)->andReturn(collect());
            $mock->shouldReceive('hasMoreWallComments')->with(1, 10, 0)->andReturn(false);
        });

        $this->get('/profile/1')
            ->assertStatus(200)
            ->assertSee('Александр')
            ->assertDontSee('class="profilelink"', false)
            ->assertDontSee('photoalbums/user/1', false)
            ->assertDontSee('videoalbums/user/1', false)
            ->assertDontSee('friends/user/1', false)
            ->assertDontSee('teams/user/1', false);
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
