<?php

namespace Tests\Feature;

use App\DTO\Profile\ImageCropData;
use App\DTO\Profile\ProfileSettingsData;
use App\Http\Middleware\TrackUserOnlineStatus;
use App\Models\User;
use App\Models\UserSetting;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use App\Service\ProfileCoverCropService;
use App\Service\ProfileImageService;
use App\Service\ProfileUpdateService;
use App\Service\UserOnlineStatusService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(TrackUserOnlineStatus::class);
        $this->mock(UserOnlineStatusService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isOnline')->byDefault()->andReturn(false);
        });
    }

    public function test_profile_edit_page_renders_settings_tabs(): void
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
            ->assertSee('id="profile-settings-active-tab"', false)
            ->assertSee('id="profile-avatar-input"', false)
            ->assertSee('id="profile-avatar-file"', false)
            ->assertSee('id="avatar-crop-modal"', false)
            ->assertSee('id="profile-cover-input"', false)
            ->assertSee('id="preview_ava"', false)
            ->assertSee('id="preview_cover"', false)
            ->assertSee(__('profile.settings.tabs.contact'))
            ->assertSee(__('profile.settings.tabs.profile'))
            ->assertSee(__('profile.settings.tabs.privacy'))
            ->assertSee(__('profile.settings.tabs.notifications'))
            ->assertSee(__('profile.settings.tabs.security'))
            ->assertSee(__('profile.settings.tabs.blacklist'))
            ->assertSee('id="profile-basic-nickname"', false)
            ->assertSee('id="profile-basic-firstname"', false)
            ->assertSee('id="profile-basic-lastname"', false)
            ->assertSee('id="profile-basic-sex"', false)
            ->assertSee('id="profile-basic-birthday"', false)
            ->assertSee('id="profile-basic-about"', false)
            ->assertSee('id="profile-basic-about-sport"', false)
            ->assertSee('id="profile-basic-country"', false)
            ->assertSee('id="profile-basic-region"', false)
            ->assertSee('Кто может писать мне сообщения')
            ->assertSee('Заявки в друзья')
            ->assertSee('Дмитрий Панкратов')
            ->assertSee('id="profile-telegram"', false)
            ->assertSee('id="profile-whatsapp"', false)
            ->assertSee('id="profile-viber"', false)
            ->assertSee('frontend/css/jquery.Jcrop.css', false)
            ->assertSee('frontend/js/jquery.Jcrop.min.js', false)
            ->assertSee('frontend/js/profile-settings.js', false);
    }

    public function test_profile_edit_post_passes_settings_to_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');

        $this->actingAs($viewer, 'web');

        $this->mock(ProfileUpdateService::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('update')
                ->once()
                ->withArgs(fn (User $user, ProfileSettingsData $data): bool => $user === $viewer
                    && $data->profile?->nickname === 'Yanack'
                    && $data->profile?->firstname === 'Alexander'
                    && $data->profile?->lastname === 'Yanickiy'
                    && $data->profile?->sex === 'male'
                    && $data->profile?->birthday === '1990-01-15'
                    && $data->profile?->about === 'I enjoy building sports communities.'
                    && $data->profile?->aboutSport === 'Running, cycling and tennis.'
                    && $data->profile?->country === 'Germany'
                    && $data->profile?->region === 'Berlin'
                    && $data->user['contact_email'] === 'new@example.test'
                    && $data->user['telegram'] === '@alex'
                    && $data->user['whatsapp'] === '+7 999 000-00-00'
                    && $data->user['viber'] === '+7 999 000-00-01'
                    && (int) $data->user['permission_send_message'] === 1
                    && $data->user['notification_friends_request'] === 'yes'
                    && $data->temporaryAvatar === '1_cropped.jpg'
                    && $data->coverFile === null);
        });

        $this->post('/profile/edit', [
            'profile' => [
                'nickname' => 'Yanack',
                'firstname' => 'Alexander',
                'lastname' => 'Yanickiy',
                'sex' => 'male',
                'birthday' => '1990-01-15',
                'about' => 'I enjoy building sports communities.',
                'about_sport' => 'Running, cycling and tennis.',
                'country' => 'Germany',
                'region' => 'Berlin',
            ],
            'user' => [
                'contact_email' => 'new@example.test',
                'phone' => '+7 999 000-00-00',
                'telegram' => '@alex',
                'whatsapp' => '+7 999 000-00-00',
                'viber' => '+7 999 000-00-01',
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
            'active_tab' => 'privacy',
        ])
            ->assertRedirect('/profile/edit#privacy')
            ->assertSessionHas('status', __('profile.messages.updated'));
    }

    public function test_profile_avatar_upload_ajax_returns_cropped_temporary_file(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 500);

        $this->actingAs($viewer, 'web');

        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('cropTemporaryAvatar')
                ->once()
                ->withArgs(fn (User $user, ImageCropData $data): bool => $user === $viewer
                    && $data->file->getClientOriginalName() === 'avatar.jpg'
                    && (int) $data->x === 10
                    && (int) $data->y === 20
                    && (int) $data->width === 300
                    && (int) $data->height === 300)
                ->andReturn([
                    'file' => '1_cropped.jpg',
                    'url' => 'http://site3.local/uploads/images/tmp/profile/avatar/1_cropped.jpg',
                ]);
        });

        $this->post('/ajax/upload_avatar', [
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

    public function test_profile_image_service_crops_avatar_to_square_temporary_image(): void
    {
        Storage::fake('public');

        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $service = new ProfileImageService();
        $result = $service->cropTemporaryAvatar($viewer, ImageCropData::fromArray([
            'file' => UploadedFile::fake()->image('avatar.png', 600, 500),
            'x' => 30,
            'y' => 40,
            'w' => 300,
            'h' => 300,
        ]));
        $path = 'images/tmp/profile/avatar/' . $result['file'];
        $temporaryFile = tempnam(sys_get_temp_dir(), 'avatar-crop-');

        Storage::disk('public')->assertExists($path);
        file_put_contents($temporaryFile, Storage::disk('public')->get($path));

        [$width, $height] = getimagesize($temporaryFile);
        unlink($temporaryFile);

        $this->assertSame(300, $width);
        $this->assertSame(300, $height);
    }

    public function test_profile_cover_crop_service_creates_header_temporary_image(): void
    {
        Storage::fake('public');

        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $service = new ProfileCoverCropService();
        $result = $service->cropTemporaryCover($viewer, ImageCropData::fromArray([
            'file' => UploadedFile::fake()->image('cover.png', 1600, 900),
            'x' => 0,
            'y' => 0,
            'w' => 1600,
            'h' => 900,
        ]));
        $path = 'images/tmp/profile/cover_page/' . $result['file'];
        $temporaryFile = tempnam(sys_get_temp_dir(), 'cover-crop-');

        Storage::disk('public')->assertExists($path);
        file_put_contents($temporaryFile, Storage::disk('public')->get($path));

        [$width, $height] = getimagesize($temporaryFile);
        unlink($temporaryFile);

        $this->assertSame(1200, $width);
        $this->assertSame(350, $height);
    }

    public function test_profile_image_service_stores_promotes_and_deletes_profile_images(): void
    {
        Storage::fake('public');

        $service = new ProfileImageService();
        Storage::disk('public')->put('images/tmp/profile/avatar/1_tmp_avatar.jpg', 'avatar');
        Storage::disk('public')->put('images/tmp/profile/cover_page/1_tmp_cover.jpg', 'cover');

        $avatar = $service->promoteTemporaryAvatar('1_tmp_avatar.jpg', 1);
        $cover = $service->promoteTemporaryCover('1_tmp_cover.jpg', 1);
        $stored = $service->storeUserImage(UploadedFile::fake()->image('cover.jpeg', 10, 10), 'user/cover_page', 1);

        $this->assertSame('1_tmp_avatar.jpg', $avatar);
        $this->assertSame('1_tmp_cover.jpg', $cover);
        Storage::disk('public')->assertExists('images/user/avatar/' . $avatar);
        Storage::disk('public')->assertExists('images/user/cover_page/' . $cover);
        Storage::disk('public')->assertMissing('images/tmp/profile/avatar/1_tmp_avatar.jpg');
        Storage::disk('public')->assertMissing('images/tmp/profile/cover_page/1_tmp_cover.jpg');
        $this->assertMatchesRegularExpression('/^1_[a-z0-9]+\.jpg$/', $stored);
        Storage::disk('public')->assertExists('images/user/cover_page/' . $stored);

        $service->deleteUserImage('user/cover_page', $stored);

        Storage::disk('public')->assertMissing('images/user/cover_page/' . $stored);
    }

    public function test_profile_page_renders_legacy_wall_layout(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $profile = $this->user(2, 'Дмитрий', 'Панкратов');
        $profile->nickname = 'ebgik';

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
                'nickname' => 'ebgik',
                'about' => '',
                'last_visit' => '18 апреля 2016 в 00:52',
                'birthday' => '16 июня 1991',
                'city' => 'Москва',
                'phone' => '+7 (966) 105-52-72',
                'contact_email' => '',
                'telegram' => '@pankratov',
                'whatsapp' => '+7 (966) 105-52-72',
                'viber' => 'viber-pankratov',
                'website' => '',
                'about_sport' => '',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect([['name' => 'Playtoget', 'description' => 'Web-developer', 'period' => '']]),
            ]);
            $mock->shouldReceive('permissions')->with($profile, $viewer, 'friend')->andReturn([
                'profile' => true,
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
            ->assertSee('Send <span>message</span>', false)
            ->assertSee('/profile/1/messages/user/2', false)
            ->assertSee('Remove<span> friend</span>', false)
            ->assertSee('id="remove_friend" data-item="2"', false)
            ->assertSee('Block')
            ->assertSee('id="block_user" data-item="2"', false)
            ->assertSee('Last seen')
            ->assertSee('18 апреля 2016 в 00:52')
            ->assertSee('Telegram')
            ->assertSee('@pankratov')
            ->assertSee('WhatsApp')
            ->assertSee('+7 (966) 105-52-72')
            ->assertSee('Viber')
            ->assertSee('viber-pankratov')
            ->assertSee('Playtoget')
            ->assertDontSee('Web-developer')
            ->assertSee('id="addCommentForm"', false)
            ->assertSee('id="message-296"', false)
            ->assertSee('Real')
            ->assertSee('/ajax/get_comments', false)
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
        $this->assertStringContainsString("url: '/ajax/remove_comment'", $commonScript);
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
                'nickname' => 'Yanack',
                'about' => '',
                'last_visit' => '5 июня 2026 в 00:06',
                'birthday' => '',
                'city' => '',
                'phone' => '',
                'contact_email' => '',
                'telegram' => '',
                'whatsapp' => '',
                'viber' => '',
                'website' => '',
                'about_sport' => 'gh',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect(),
            ]);
            $mock->shouldReceive('permissions')->with($viewer, $viewer, '')->andReturn([
                'profile' => true,
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
                'nickname' => '',
                'about' => '',
                'last_visit' => '31 июля 2019 в 18:07',
                'birthday' => '',
                'city' => '',
                'phone' => '',
                'contact_email' => '',
                'telegram' => '',
                'whatsapp' => '',
                'viber' => '',
                'website' => '',
                'about_sport' => '',
                'is_online' => false,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect(),
            ]);
            $mock->shouldReceive('permissions')->with($profile, null, 'nofriend')->andReturn([
                'profile' => true,
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
            ->assertSee('Log in to leave a comment')
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
            'status' => 1,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
