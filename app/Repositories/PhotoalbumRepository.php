<?php

namespace App\Repositories;

use App\DTO\Album\AlbumData;
use App\DTO\Photo\PhotoUploadData;
use App\Helpers\FrontAssets;
use App\Models\Attachment;
use App\Models\Photo;
use App\Models\PhotoAlbums;
use App\Models\User;
use App\Repositories\Concerns\DeletesContentRelations;
use App\Service\AlbumPhotoStorageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PhotoalbumRepository extends BaseRepository
{
    use DeletesContentRelations;

    private const USER_TYPES = ['user', 'user_attach'];

    public function __construct(
        PhotoAlbums $model,
        private readonly AlbumPhotoStorageService $photos
    )
    {
        parent::__construct($model);
    }

    public function albumsForUser(int $userId): Collection
    {
        return $this->albumsForOwner($userId, 'user');
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @return Collection
     */
    public function albumsForOwner(int $ownerId, string $type): Collection
    {
        return $this->model->newQuery()
            ->with(['photos' => fn ($query) => $query
                ->where('banned', false)
                ->latest('id')
                ->limit(1)])
            ->where('owner_id', $ownerId)
            ->where('photoalbumable_type', $type)
            ->orderByDesc('id')
            ->get()
            ->map(fn (PhotoAlbums $album): array => $this->serializeAlbum($album));
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function photosForUser(int $userId, int $limit = 6, int $offset = 0): Collection
    {
        return $this->photosForOwner($userId, 'user', $limit, $offset);
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function photosForOwner(int $ownerId, string $type, int $limit = 6, int $offset = 0): Collection
    {
        return Photo::query()
            ->with(['album'])
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $ownerId)
                ->where('photoalbumable_type', $type))
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Photo $photo): array => $this->serializePhoto($photo));
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreUserPhotos(int $userId, int $limit, int $offset): bool
    {
        return $this->hasMoreOwnerPhotos($userId, 'user', $limit, $offset);
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreOwnerPhotos(int $ownerId, string $type, int $limit, int $offset): bool
    {
        return Photo::query()
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $ownerId)
                ->where('photoalbumable_type', $type))
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param string $type
     * @return Collection
     */
    public function popularPhotos(int $limit = 9, int $offset = 0, string $type = 'user'): Collection
    {
        return Photo::query()
            ->with(['album'])
            ->select('photos.*')
            ->join('photoalbums', 'photoalbums.id', '=', 'photos.photoalbum_id')
            ->join('likes', function ($join): void {
                $join->on('likes.content_id', '=', 'photos.id')
                    ->where('likes.likeable_type', 'photo');
            })
            ->where('photos.banned', false)
            ->where('photoalbums.photoalbumable_type', $type)
            ->groupBy(
                'photos.id',
                'photos.photoalbum_id',
                'photos.small_photo',
                'photos.photo',
                'photos.description',
                'photos.owner_id',
                'photos.banned',
                'photos.moderate',
                'photos.created_at',
                'photos.updated_at',
            )
            ->orderByRaw('COUNT(likes.user_id) DESC')
            ->orderByDesc('photos.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Photo $photo): array => $this->serializePhoto($photo));
    }

    /**
     * @param int $albumId
     * @param array|null $types
     * @return PhotoAlbums|null
     */
    public function album(int $albumId, ?array $types = null): ?PhotoAlbums
    {
        /** @var PhotoAlbums|null $album */
        $album = $this->model->newQuery()
            ->with('owner.settings')
            ->whereKey($albumId)
            ->whereIn('photoalbumable_type', $types ?: self::USER_TYPES)
            ->first();

        return $album;
    }

    /**
     * @param PhotoAlbums $album
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function albumPhotos(PhotoAlbums $album, int $limit = 9, int $offset = 0): Collection
    {
        return $album->photos()
            ->with('album')
            ->where('banned', false)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Photo $photo): array => $this->serializePhoto($photo));
    }

    /**
     * @param PhotoAlbums $album
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreAlbumPhotos(PhotoAlbums $album, int $limit, int $offset): bool
    {
        return $album->photos()
            ->where('banned', false)
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    public function editableAlbumsFor(User $user): Collection
    {
        return $this->editableAlbumsForOwner($user->id, 'user');
    }

    public function editableAlbumsForOwner(int $ownerId, string $type): Collection
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('photoalbumable_type', $type)
            ->orderBy('name')
            ->get();
    }

    public function ensureDefaultAlbum(User $user): PhotoAlbums
    {
        return $this->ensureDefaultAlbumForOwner($user->id, 'user', 'Мой альбом');
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @param string $name
     * @return PhotoAlbums
     */
    public function ensureDefaultAlbumForOwner(int $ownerId, string $type, string $name = 'Мой альбом'): PhotoAlbums
    {
        /** @var PhotoAlbums $album */
        $album = $this->model->newQuery()->firstOrCreate([
            'owner_id' => $ownerId,
            'photoalbumable_type' => $type,
        ], [
            'name' => $name,
        ]);

        return $album;
    }

    /**
     * @param User $user
     * @param AlbumData $data
     * @return PhotoAlbums
     */
    public function createUserAlbum(User $user, AlbumData $data): PhotoAlbums
    {
        return $this->createAlbumForOwner($user->id, 'user', $data);
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @param AlbumData $data
     * @return PhotoAlbums
     */
    public function createAlbumForOwner(int $ownerId, string $type, AlbumData $data): PhotoAlbums
    {
        /** @var PhotoAlbums $album */
        $album = $this->model->newQuery()->create([
            'name' => $data->name,
            'photoalbumable_type' => $type,
            'owner_id' => $ownerId,
        ]);

        return $album;
    }

    /**
     * @param PhotoAlbums $album
     * @param AlbumData $data
     * @return bool
     */
    public function updateUserAlbum(PhotoAlbums $album, AlbumData $data): bool
    {
        return $album->fill($data->toArray())->save();
    }

    /**
     * @param User $user
     * @param string $name
     * @param int|null $exceptId
     * @return bool
     */
    public function nameExists(User $user, string $name, ?int $exceptId = null): bool
    {
        return $this->nameExistsForOwner($user->id, 'user', $name, $exceptId);
    }

    /**
     * @param int $ownerId
     * @param string $type
     * @param string $name
     * @param int|null $exceptId
     * @return bool
     */
    public function nameExistsForOwner(int $ownerId, string $type, string $name, ?int $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('photoalbumable_type', $type)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function isOwner(PhotoAlbums $album, ?User $user): bool
    {
        return $user && (int) $album->owner_id === (int) $user->id;
    }

    /**
     * @param User $user
     * @param PhotoAlbums $album
     * @param PhotoUploadData $data
     * @return Photo
     */
    public function storePhoto(User $user, PhotoAlbums $album, PhotoUploadData $data): Photo
    {
        if (! $this->isOwner($album, $user) || $album->photoalbumable_type !== 'user') {
            throw new RuntimeException('Нет доступа к выбранному альбому.');
        }

        return $this->storePhotoForAlbum($user, $album, $data);
    }

    /**
     * @param User $user
     * @param PhotoAlbums $album
     * @param PhotoUploadData $data
     * @return Photo
     */
    public function storePhotoForAlbum(User $user, PhotoAlbums $album, PhotoUploadData $data): Photo
    {
        $storedPhoto = $this->photos->storePhoto($data->file, $album->photoalbumable_type);

        /** @var Photo $photo */
        $photo = Photo::query()->create([
            'photoalbum_id' => $album->id,
            'small_photo' => $storedPhoto['small_photo'],
            'photo' => $storedPhoto['photo'],
            'description' => $data->description,
            'owner_id' => $user->id,
            'banned' => false,
            'moderate' => false,
        ])->load('album');

        return $photo;
    }

    /**
     * @param User $user
     * @param PhotoUploadData $data
     * @return Photo
     */
    public function storeAttachmentPhoto(User $user, PhotoUploadData $data): Photo
    {
        $storedPhoto = $this->photos->storeAttachmentPhoto($user, $data->file);
        /** @var PhotoAlbums $album */
        $album = $storedPhoto['album'];

        /** @var Photo $photo */
        $photo = Photo::query()->create([
            'photoalbum_id' => $album->id,
            'small_photo' => $storedPhoto['small_photo'],
            'photo' => $storedPhoto['photo'],
            'description' => '',
            'owner_id' => $user->id,
            'banned' => false,
            'moderate' => false,
        ])->load('album');

        return $photo;
    }

    /**
     * @param int $photoId
     * @param array|null $types
     * @return Photo|null
     */
    public function photo(int $photoId, ?array $types = null): ?Photo
    {
        /** @var Photo|null $photo */
        $photo = Photo::query()
            ->with('album')
            ->whereKey($photoId)
            ->when($types, fn ($query) => $query->whereHas('album', fn ($albumQuery) => $albumQuery
                ->whereIn('photoalbumable_type', $types)))
            ->first();

        return $photo;
    }

    /**
     * @param Photo $photo
     * @return bool
     * @throws \Throwable
     */
    public function deletePhoto(Photo $photo): bool
    {
        return DB::transaction(function () use ($photo): bool {
            $this->deletePhotoFiles($photo);
            $this->deletePhotoRelations($photo);

            return (bool) $photo->delete();
        });
    }

    /**
     * @param PhotoAlbums $album
     * @return bool
     * @throws \Throwable
     */
    public function deleteAlbum(PhotoAlbums $album): bool
    {
        return DB::transaction(function () use ($album): bool {
            $album->loadMissing('photos.album');

            foreach ($album->photos as $photo) {
                $this->deletePhotoFiles($photo);
                $this->deletePhotoRelations($photo);
                $photo->delete();
            }

            return (bool) $album->delete();
        });
    }

    public function deletePhotoFor(User $user, int $photoId): bool
    {
        /** @var Photo|null $photo */
        $photo = Photo::query()
            ->with('album')
            ->whereKey($photoId)
            ->first();

        if (! $photo || (int) $photo->owner_id !== (int) $user->id) {
            return false;
        }

        return $this->deletePhoto($photo);
    }

    public function serializePhoto(Photo $photo): array
    {
        $photo->loadMissing('album');

        return [
            'id' => (int) $photo->id,
            'small' => FrontAssets::photoGallery($photo),
            'big' => FrontAssets::photoGallery($photo, 'photo') ?: FrontAssets::photoGallery($photo),
            'description' => (string) $photo->description,
            'owner_id' => (int) $photo->owner_id,
            'type' => (string) ($photo->album?->photoalbumable_type ?? 'user'),
        ];
    }

    public function serializeAlbum(PhotoAlbums $album): array
    {
        $album->loadMissing(['photos' => fn ($query) => $query
            ->where('banned', false)
            ->latest('id')
            ->limit(1)]);

        /** @var Photo|null $photo */
        $photo = $album->photos->first();

        return [
            'id' => (int) $album->id,
            'name' => (string) $album->name,
            'type' => (string) $album->photoalbumable_type,
            'owner_id' => (int) $album->owner_id,
            'image' => $photo ? FrontAssets::photoGallery($photo) : null,
        ];
    }

    private function deletePhotoRelations(Photo $photo): void
    {
        $this->deleteContentRelations('photo', (int) $photo->id);

        Attachment::query()
            ->where('photo_id', $photo->id)
            ->delete();
    }

    private function deletePhotoFiles(Photo $photo): void
    {
        $type = $photo->album?->photoalbumable_type ?: 'user';
        $disk = Storage::disk('public');

        foreach (array_filter([$photo->small_photo, $photo->photo]) as $filename) {
            $disk->delete('images/photogallery/' . $type . '/' . $filename);

            $legacyPath = public_path('uploads/images/photogallery/' . $type . '/' . $filename);
            if (is_file($legacyPath)) {
                @unlink($legacyPath);
            }
        }
    }

}
