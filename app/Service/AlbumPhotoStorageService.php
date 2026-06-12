<?php

namespace App\Service;

use App\Models\PhotoAlbums;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AlbumPhotoStorageService
{
    private ImageFileService $images;

    /**
     * Подключает сервис для работы с загруженными изображениями.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Сохраняет фотографию и уменьшенную копию в публичном хранилище.
     *
     * @param UploadedFile $file
     * @param string|null $albumType
     * @return string[]
     */
    public function storePhoto(UploadedFile $file, ?string $albumType): array
    {
        $filename = $this->images->hashedFilename($file);
        $smallFilename = 's_' . $filename;
        $directory = 'images/photogallery/' . ($albumType ?: 'user');

        $source = $this->images->imageResource($file, true);
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

        return [
            'photo' => $filename,
            'small_photo' => $smallFilename,
        ];
    }

    /**
     * Сохраняет прикрепленную фотографию пользователя и возвращает данные для записи.
     *
     * @param User $user
     * @param UploadedFile $file
     * @return array
     */
    public function storeAttachmentPhoto(User $user, UploadedFile $file): array
    {
        /** @var PhotoAlbums $album */
        $album = PhotoAlbums::query()->firstOrCreate([
            'owner_id' => $user->id,
            'photoalbumable_type' => 'user_attach',
        ], [
            'name' => 'Мои прикрепленные фотографии',
        ]);

        $filename = $this->images->randomFilename($file);
        $smallFilename = 's_' . $filename;
        $directory = 'images/photogallery/user_attach';
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Invalid file');
        }

        $disk = Storage::disk('public');
        $originalPath = $directory . '/' . $filename;
        $smallPath = $directory . '/' . $smallFilename;

        if (! $disk->put($originalPath, $contents) || ! $disk->put($smallPath, $contents)) {
            $disk->delete([$originalPath, $smallPath]);

            throw new RuntimeException('File was not saved');
        }

        return [
            'album' => $album,
            'photo' => $filename,
            'small_photo' => $smallFilename,
        ];
    }

    /**
     * Готовит содержимое уменьшенного изображения с сохранением пропорций.
     *
     * @param GdImage $source
     * @param string|null $mime
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @return string
     */
    private function resizedImageContents(GdImage $source, ?string $mime, ?int $maxWidth, ?int $maxHeight): string
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

}
