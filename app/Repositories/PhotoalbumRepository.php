<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Photo;
use App\Models\Photoalbum;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PhotoalbumRepository extends BaseRepository
{
    private const USER_TYPES = ['user', 'user_attach'];

    public function __construct(Photoalbum $model)
    {
        parent::__construct($model);
    }

    public function albumsForUser(int $userId): Collection
    {
        return $this->model->newQuery()
            ->with(['photos' => fn ($query) => $query
                ->where('banned', false)
                ->latest('id')
                ->limit(1)])
            ->where('owner_id', $userId)
            ->whereIn('photoalbumable_type', self::USER_TYPES)
            ->orderBy('photoalbumable_type')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Photoalbum $album): array => $this->serializeAlbum($album));
    }

    public function photosForUser(int $userId, int $limit = 6, int $offset = 0): Collection
    {
        return Photo::query()
            ->with(['album'])
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $userId)
                ->where('photoalbumable_type', 'user'))
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Photo $photo): array => $this->serializePhoto($photo));
    }

    public function hasMoreUserPhotos(int $userId, int $limit, int $offset): bool
    {
        return Photo::query()
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $userId)
                ->where('photoalbumable_type', 'user'))
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    public function popularPhotos(int $limit = 9, int $offset = 0): Collection
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
            ->where('photoalbums.photoalbumable_type', 'user')
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

    public function album(int $albumId): ?Photoalbum
    {
        /** @var Photoalbum|null $album */
        $album = $this->model->newQuery()
            ->with('owner.settings')
            ->whereKey($albumId)
            ->whereIn('photoalbumable_type', self::USER_TYPES)
            ->first();

        return $album;
    }

    public function albumPhotos(Photoalbum $album, int $limit = 9, int $offset = 0): Collection
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

    public function hasMoreAlbumPhotos(Photoalbum $album, int $limit, int $offset): bool
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
        return $this->model->newQuery()
            ->where('owner_id', $user->id)
            ->where('photoalbumable_type', 'user')
            ->orderBy('name')
            ->get();
    }

    public function ensureDefaultAlbum(User $user): Photoalbum
    {
        /** @var Photoalbum $album */
        $album = $this->model->newQuery()->firstOrCreate([
            'owner_id' => $user->id,
            'photoalbumable_type' => 'user',
        ], [
            'name' => 'Мой альбом',
        ]);

        return $album;
    }

    public function createUserAlbum(User $user, string $name): Photoalbum
    {
        /** @var Photoalbum $album */
        $album = $this->model->newQuery()->create([
            'name' => $name,
            'photoalbumable_type' => 'user',
            'owner_id' => $user->id,
        ]);

        return $album;
    }

    public function updateUserAlbum(Photoalbum $album, string $name): bool
    {
        return $album->fill(['name' => $name])->save();
    }

    public function nameExists(User $user, string $name, ?int $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('owner_id', $user->id)
            ->where('photoalbumable_type', 'user')
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function isOwner(Photoalbum $album, ?User $user): bool
    {
        return $user && (int) $album->owner_id === (int) $user->id;
    }

    public function storePhoto(User $user, Photoalbum $album, UploadedFile $file, string $description = ''): Photo
    {
        if (! $this->isOwner($album, $user) || $album->photoalbumable_type !== 'user') {
            throw new RuntimeException('Нет доступа к выбранному альбому.');
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;
        $smallFilename = 's_' . $filename;
        $directory = 'images/photogallery/user';

        $source = $this->imageResource($file);
        $original = $this->resizedImageContents($source, $file->getMimeType(), 800, null);
        $thumb = $this->resizedImageContents($source, $file->getMimeType(), null, 300);
        imagedestroy($source);

        $disk = Storage::disk('public');
        $originalPath = $directory . '/' . $filename;
        $smallPath = $directory . '/' . $smallFilename;

        if (! $disk->put($originalPath, $original) || ! $disk->put($smallPath, $thumb)) {
            $disk->delete([$originalPath, $smallPath]);

            throw new RuntimeException('Не удалось сохранить фотографию.');
        }

        /** @var Photo $photo */
        $photo = Photo::query()->create([
            'photoalbum_id' => $album->id,
            'small_photo' => $smallFilename,
            'photo' => $filename,
            'description' => $description,
            'owner_id' => $user->id,
            'banned' => false,
            'moderate' => false,
        ])->load('album');

        return $photo;
    }

    public function deleteAlbum(Photoalbum $album): bool
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

        return DB::transaction(function () use ($photo): bool {
            $this->deletePhotoFiles($photo);
            $this->deletePhotoRelations($photo);

            return (bool) $photo->delete();
        });
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

    public function serializeAlbum(Photoalbum $album): array
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
        Comment::query()
            ->where('commentable_type', 'photo')
            ->where('content_id', $photo->id)
            ->delete();

        Like::query()
            ->where('likeable_type', 'photo')
            ->where('content_id', $photo->id)
            ->delete();

        Share::query()
            ->where('shareable_type', 'photo')
            ->where('content_id', $photo->id)
            ->delete();

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

    private function imageResource(UploadedFile $file): \GdImage
    {
        $path = $file->getRealPath();
        $image = match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            default => false,
        };

        if (! $image instanceof \GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $file->getMimeType() === 'image/jpeg' || $file->getMimeType() === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    private function resizedImageContents(\GdImage $source, ?string $mime, ?int $maxWidth, ?int $maxHeight): string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = 1.0;

        if ($maxWidth && $sourceWidth > $maxWidth) {
            $ratio = min($ratio, $maxWidth / $sourceWidth);
        }

        if ($maxHeight && $sourceHeight > $maxHeight) {
            $ratio = min($ratio, $maxHeight / $sourceHeight);
        }

        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        } else {
            imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();

        match ($mime) {
            'image/png' => imagepng($target, null, 8),
            'image/gif' => imagegif($target),
            default => imagejpeg($target, null, 90),
        };

        $contents = ob_get_clean();
        imagedestroy($target);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException('Не удалось обработать изображение.');
        }

        return $contents;
    }

    private function orientJpeg(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 0) : 0;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if (! $rotated instanceof \GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
