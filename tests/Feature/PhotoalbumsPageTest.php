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
use App\Service\AlbumPhotoStorageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    public function test_album_photo_storage_service_saves_resized_photo_and_thumbnail(): void
    {
        Storage::fake('public');

        $service = new AlbumPhotoStorageService();
        $result = $service->storePhoto(UploadedFile::fake()->image('photo.jpg', 1200, 900), 'user');

        $originalPath = 'images/photogallery/user/' . $result['photo'];
        $smallPath = 'images/photogallery/user/' . $result['small_photo'];
        $originalFile = tempnam(sys_get_temp_dir(), 'album-photo-');
        $smallFile = tempnam(sys_get_temp_dir(), 'album-thumb-');

        Storage::disk('public')->assertExists($originalPath);
        Storage::disk('public')->assertExists($smallPath);
        file_put_contents($originalFile, Storage::disk('public')->get($originalPath));
        file_put_contents($smallFile, Storage::disk('public')->get($smallPath));

        [$originalWidth, $originalHeight] = getimagesize($originalFile);
        [$smallWidth, $smallHeight] = getimagesize($smallFile);
        unlink($originalFile);
        unlink($smallFile);

        $this->assertSame(800, $originalWidth);
        $this->assertSame(600, $originalHeight);
        $this->assertSame(400, $smallWidth);
        $this->assertSame(300, $smallHeight);
    }

    public function test_album_photo_storage_service_saves_attachment_photo_and_album(): void
    {
        $this->createPhotoalbumsTable();
        Storage::fake('public');

        try {
            $viewer = $this->user(1, 'Александр', 'Яницкий');
            $service = new AlbumPhotoStorageService();
            $result = $service->storeAttachmentPhoto($viewer, UploadedFile::fake()->image('attach.jpeg', 600, 400));

            $this->assertInstanceOf(PhotoAlbums::class, $result['album']);
            $this->assertSame('Мои прикрепленные фотографии', $result['album']->name);
            $this->assertSame('user_attach', $result['album']->photoalbumable_type);
            $this->assertMatchesRegularExpression('/^[a-z0-9]+\.jpg$/', $result['photo']);
            $this->assertSame('s_' . $result['photo'], $result['small_photo']);
            Storage::disk('public')->assertExists('images/photogallery/user_attach/' . $result['photo']);
            Storage::disk('public')->assertExists('images/photogallery/user_attach/' . $result['small_photo']);
        } finally {
            Schema::dropIfExists('photoalbums');
        }
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

    private function createPhotoalbumsTable(): void
    {
        Schema::dropIfExists('photoalbums');
        Schema::create('photoalbums', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('photoalbumable_type')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();
        });
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
