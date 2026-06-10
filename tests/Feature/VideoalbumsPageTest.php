<?php

namespace Tests\Feature;

use App\DTO\Album\AlbumData;
use App\DTO\Video\VideoData;
use App\Models\User;
use App\Models\Video;
use App\Models\Videoalbum;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\VideoalbumRepository;
use Mockery\MockInterface;
use Tests\TestCase;

class VideoalbumsPageTest extends TestCase
{
    public function test_videoalbums_page_renders_legacy_sections(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $this->actingAs($viewer, 'web');

        $this->mockProfile($viewer);
        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->andReturn('self');
        });
        $this->mock(VideoalbumRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('videosForUser')->with($viewer->id, 6, 0)->andReturn(collect([
                $this->videoPayload(10),
            ]));
            $mock->shouldReceive('hasMoreUserVideos')->with($viewer->id, 6, 0)->andReturn(true);
            $mock->shouldReceive('popularVideos')->with(6, 0)->andReturn(collect([
                $this->videoPayload(11),
            ]));
            $mock->shouldReceive('albumsForUser')->with($viewer->id)->andReturn(collect([
                ['id' => 19, 'name' => 'Мой альбом', 'image' => 'https://img.youtube.com/vi/test/hqdefault.jpg'],
            ]));
        });

        $this->get('/videoalbums')
            ->assertStatus(200)
            ->assertSee('Добавить видео')
            ->assertSee('/videoalbums/add-video', false)
            ->assertSee('/videoalbums/create', false)
            ->assertSee('Популярные видео')
            ->assertSee('Мои альбомы')
            ->assertSee('Мои видео')
            ->assertSee('/videoalbums/19', false)
            ->assertSee('showMoreVideos')
            ->assertSee('/ajax', false);
    }

    public function test_add_video_page_renders_form(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new Videoalbum(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'videoalbumable_type' => 'user']);
        $album->id = 19;
        $album->exists = true;

        $this->actingAs($viewer, 'web');

        $this->mock(VideoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $mock->shouldReceive('ensureDefaultAlbum')->with($viewer)->andReturn($album);
            $mock->shouldReceive('editableAlbumsFor')->with($viewer)->andReturn(collect([$album]));
        });

        $this->get('/videoalbums/add-video')
            ->assertStatus(200)
            ->assertSee('Добавление видео')
            ->assertSee('name="video"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="videoalbum_id"', false)
            ->assertSee('/videoalbums/add-video', false);
    }

    public function test_create_videoalbum_post_creates_album(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $this->actingAs($viewer, 'web');

        $this->mock(VideoalbumRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('nameExists')->with($viewer, 'Новый альбом')->andReturn(false);
            $mock->shouldReceive('createUserAlbum')
                ->once()
                ->withArgs(fn (User $user, AlbumData $data): bool => $user === $viewer && $data->name === 'Новый альбом')
                ->andReturn(new Videoalbum());
        });

        $this->post('/videoalbums/create', ['name' => 'Новый альбом'])
            ->assertRedirect('/videoalbums');
    }

    public function test_add_video_post_uses_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new Videoalbum(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'videoalbumable_type' => 'user']);
        $album->id = 19;
        $album->exists = true;

        $this->actingAs($viewer, 'web');

        $this->mock(VideoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $video = new Video();
            $video->id = 10;
            $video->exists = true;

            $mock->shouldReceive('album')->with(19)->andReturn($album);
            $mock->shouldReceive('isOwner')->with($album, $viewer)->andReturn(true);
            $mock->shouldReceive('addUserVideo')
                ->once()
                ->withArgs(fn (User $user, Videoalbum $receivedAlbum, VideoData $data): bool => $user === $viewer
                    && $receivedAlbum === $album
                    && $data->link === 'https://youtu.be/abc123'
                    && $data->description === 'Описание'
                    && $data->albumId === 19)
                ->andReturn($video);
        });

        $this->post('/videoalbums/add-video', [
            'video' => 'https://youtu.be/abc123',
            'description' => 'Описание',
            'videoalbum_id' => 19,
        ])
            ->assertRedirect('/videoalbums');
    }

    public function test_album_page_renders_videos_and_lazy_load_marker(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new Videoalbum(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'videoalbumable_type' => 'user']);
        $album->id = 19;
        $album->exists = true;
        $album->setRelation('owner', $viewer);

        $this->actingAs($viewer, 'web');

        $this->mockProfile($viewer);
        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->andReturn('self');
        });
        $this->mock(VideoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $mock->shouldReceive('album')->with(19)->andReturn($album);
            $mock->shouldReceive('albumVideos')->with($album, 6, 0)->andReturn(collect([
                $this->videoPayload(10),
            ]));
            $mock->shouldReceive('hasMoreAlbumVideos')->with($album, 6, 0)->andReturn(true);
            $mock->shouldReceive('isOwner')->with($album, $viewer)->andReturn(true);
        });

        $this->get('/videoalbums/19')
            ->assertStatus(200)
            ->assertSee('Мой альбом')
            ->assertSee('id="album-video-list"', false)
            ->assertSee('data-album-id="19"', false)
            ->assertSee('/ajax', false);
    }

    private function mockProfile(User $viewer): void
    {
        $this->mock(ProfileRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('profile')->with($viewer->id)->andReturn($viewer);
            $mock->shouldReceive('profileData')->with($viewer)->andReturn([
                'avatar' => 'http://site3.local/uploads/images/user/avatar/1.jpg',
                'cover' => 'http://site3.local/uploads/images/user/cover_page/1.jpg',
                'firstname' => 'Александр',
                'lastname' => 'Яницкий',
                'secondname' => '',
                'about' => '',
                'last_visit' => '',
                'birthday' => '',
                'city' => '',
                'phone' => '',
                'contact_email' => '',
                'skype' => '',
                'website' => '',
                'about_sport' => '',
                'is_online' => true,
                'sport_types' => collect(),
                'education' => collect(),
                'work' => collect(),
            ]);
            $mock->shouldReceive('permissions')->with($viewer, $viewer, 'self')->andReturn([
                'send_message' => false,
                'wall' => true,
                'photo' => true,
                'video' => true,
                'friends' => true,
                'teams' => false,
            ]);
        });
    }

    private function videoPayload(int $id): array
    {
        return [
            'id' => $id,
            'thumb' => 'https://img.youtube.com/vi/video' . $id . '/hqdefault.jpg',
            'player' => '<iframe></iframe>',
            'description' => 'Видео ' . $id,
            'owner_id' => 1,
            'views_count' => 0,
            'type' => 'user',
        ];
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
