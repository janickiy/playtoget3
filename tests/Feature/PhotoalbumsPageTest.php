<?php

namespace Tests\Feature;

use App\DTO\Album\AlbumData;
use App\DTO\Photo\PhotoUploadData;
use App\Models\PhotoAlbums;
use App\Models\Photo;
use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

class PhotoalbumsPageTest extends TestCase
{
    public function test_photoalbums_page_renders_legacy_sections(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $this->actingAs($viewer, 'web');

        $this->mockProfile($viewer);
        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->andReturn('self');
        });
        $this->mock(PhotoalbumRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('photosForUser')->with($viewer->id, 6, 0)->andReturn(collect([
                $this->photoPayload(10),
            ]));
            $mock->shouldReceive('hasMoreUserPhotos')->with($viewer->id, 6, 0)->andReturn(true);
            $mock->shouldReceive('popularPhotos')->with(9, 0)->andReturn(collect([
                $this->photoPayload(11),
            ]));
            $mock->shouldReceive('albumsForUser')->with($viewer->id)->andReturn(collect([
                ['id' => 64, 'name' => 'Мой альбом', 'image' => 'http://site3.local/uploads/images/album.jpg'],
            ]));
        });

        $this->get('/photoalbums')
            ->assertStatus(200)
            ->assertSee('Добавить фото')
            ->assertSee('/photoalbums/add-photo', false)
            ->assertSee('/photoalbums/create', false)
            ->assertSee('Популярные фото')
            ->assertSee('Мои альбомы')
            ->assertSee('Мои фото')
            ->assertSee('/photoalbums/64', false)
            ->assertSee('showMorePhotos')
            ->assertSee('/ajax', false);
    }

    public function test_add_photo_page_renders_upload_queue(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new PhotoAlbums(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'photoalbumable_type' => 'user']);
        $album->id = 64;
        $album->exists = true;

        $this->actingAs($viewer, 'web');

        $this->mock(PhotoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $mock->shouldReceive('ensureDefaultAlbum')->with($viewer)->andReturn($album);
            $mock->shouldReceive('editableAlbumsFor')->with($viewer)->andReturn(collect([$album]));
        });

        $this->get('/photoalbums/add-photo')
            ->assertStatus(200)
            ->assertSee('Добавление фото')
            ->assertSee('id="photo-upload-form"', false)
            ->assertSee('id="photo-files"', false)
            ->assertSee('frontend/js/photo-upload.js', false)
            ->assertSee('/ajax/add_photo_ajax', false)
            ->assertSee('/photoalbums', false);
    }

    public function test_create_photoalbum_post_creates_album(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $this->actingAs($viewer, 'web');

        $this->mock(PhotoalbumRepository::class, function (MockInterface $mock) use ($viewer): void {
            $mock->shouldReceive('nameExists')->with($viewer, 'Новый альбом')->andReturn(false);
            $mock->shouldReceive('createUserAlbum')
                ->once()
                ->withArgs(fn (User $user, AlbumData $data): bool => $user === $viewer && $data->name === 'Новый альбом');
        });

        $this->post('/photoalbums/create', ['name' => 'Новый альбом'])
            ->assertRedirect('/photoalbums');
    }

    public function test_album_page_renders_photos_and_lazy_load_marker(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new PhotoAlbums(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'photoalbumable_type' => 'user']);
        $album->id = 64;
        $album->exists = true;
        $album->setRelation('owner', $viewer);

        $this->actingAs($viewer, 'web');

        $this->mockProfile($viewer);
        $this->mock(FriendRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('friendshipStatus')->andReturn('self');
        });
        $this->mock(PhotoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $mock->shouldReceive('album')->with(64)->andReturn($album);
            $mock->shouldReceive('albumPhotos')->with($album, 9, 0)->andReturn(collect([
                $this->photoPayload(10),
            ]));
            $mock->shouldReceive('hasMoreAlbumPhotos')->with($album, 9, 0)->andReturn(true);
            $mock->shouldReceive('isOwner')->with($album, $viewer)->andReturn(true);
        });

        $this->get('/photoalbums/64')
            ->assertStatus(200)
            ->assertSee('Мой альбом')
            ->assertSee('id="album-photo-list"', false)
            ->assertSee('data-album-id="64"', false)
            ->assertSee('/ajax', false);
    }

    public function test_add_photo_ajax_uses_repository(): void
    {
        $viewer = $this->user(1, 'Александр', 'Яницкий');
        $album = new PhotoAlbums(['name' => 'Мой альбом', 'owner_id' => $viewer->id, 'photoalbumable_type' => 'user']);
        $album->id = 64;
        $album->exists = true;

        $this->actingAs($viewer, 'web');

        $this->mock(PhotoalbumRepository::class, function (MockInterface $mock) use ($viewer, $album): void {
            $photo = new Photo();
            $photo->id = 10;
            $photo->exists = true;

            $mock->shouldReceive('album')->with(64)->andReturn($album);
            $mock->shouldReceive('isOwner')->with($album, $viewer)->andReturn(true);
            $mock->shouldReceive('storePhoto')
                ->once()
                ->withArgs(fn (User $user, PhotoAlbums $receivedAlbum, PhotoUploadData $data): bool => $user === $viewer
                    && $receivedAlbum === $album
                    && $data->file->getClientOriginalName() === 'photo.jpg'
                    && $data->description === 'Описание')
                ->andReturn($photo);
        });

        $this->post('/ajax/add_photo_ajax', [
            'categorie' => 64,
            'description' => 'Описание',
            'file' => UploadedFile::fake()->image('photo.jpg', 600, 400),
        ])
            ->assertStatus(200)
            ->assertJson([
                'info' => 'FILE_SUCCESSFULLY_DOWNLOADED',
                'id' => 10,
                'error' => null,
            ]);
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

    private function photoPayload(int $id): array
    {
        return [
            'id' => $id,
            'small' => 'http://site3.local/uploads/images/photogallery/user/s_' . $id . '.jpg',
            'big' => 'http://site3.local/uploads/images/photogallery/user/' . $id . '.jpg',
            'description' => 'Фото ' . $id,
            'owner_id' => 1,
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
