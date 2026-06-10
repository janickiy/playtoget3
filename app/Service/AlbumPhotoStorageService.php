<?php

namespace App\Service;

use App\Models\PhotoAlbums;
use App\Models\User;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AlbumPhotoStorageService
{
    /**
     * @param UploadedFile $file
     * @param string|null $albumType
     * @return string[]
     */
    public function storePhoto(UploadedFile $file, ?string $albumType): array
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;
        $smallFilename = 's_' . $filename;
        $directory = 'images/photogallery/' . ($albumType ?: 'user');

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

        return [
            'photo' => $filename,
            'small_photo' => $smallFilename,
        ];
    }

    /**
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

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(Str::random(32)) . '.' . $extension;
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
     * @param UploadedFile $file
     * @return GdImage
     */
    private function imageResource(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();
        $image = match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $file->getMimeType() === 'image/jpeg' || $file->getMimeType() === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    /**
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

    /**
     * @param GdImage $image
     * @param string $path
     * @return GdImage
     */
    private function orientJpeg(GdImage $image, string $path): GdImage
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

        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
