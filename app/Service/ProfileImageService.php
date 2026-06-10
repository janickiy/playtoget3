<?php

namespace App\Service;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileImageService
{
    public function storeUserImage(UploadedFile $file, string $directory, int $userId): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = sprintf('%d_%s.%s', $userId, Str::lower(Str::random(32)), $extension);
        $path = 'images/' . trim($directory, '/') . '/' . $filename;
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение профиля.');
        }

        return $filename;
    }

    public function promoteTemporaryAvatar(?string $temporaryAvatar, int $userId): ?string
    {
        if (! $temporaryAvatar) {
            return null;
        }

        return $this->promoteTemporaryImage(
            $temporaryAvatar,
            $userId,
            'images/tmp/profile/avatar/',
            'images/user/avatar/',
            'Некорректное имя файла аватара.',
            'Файл аватара не найден.',
            'Не удалось сохранить аватар.',
        );
    }

    public function promoteTemporaryCover(?string $temporaryCover, int $userId): ?string
    {
        if (! $temporaryCover) {
            return null;
        }

        return $this->promoteTemporaryImage(
            $temporaryCover,
            $userId,
            'images/tmp/profile/cover_page/',
            'images/user/cover_page/',
            'Некорректное имя файла обложки.',
            'Файл обложки не найден.',
            'Не удалось сохранить обложку.',
        );
    }

    public function imageResource(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $mime === 'image/jpeg' || $mime === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    public function deleteUserImage(string $directory, ?string $filename): void
    {
        if (! $filename) {
            return;
        }

        Storage::disk('public')->delete('images/' . trim($directory, '/') . '/' . $filename);
    }

    private function promoteTemporaryImage(
        string $temporaryImage,
        int $userId,
        string $sourceDirectory,
        string $targetDirectory,
        string $invalidNameMessage,
        string $missingFileMessage,
        string $copyFailedMessage,
    ): string {
        $filename = basename($temporaryImage);

        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $filename)) {
            throw new RuntimeException($invalidNameMessage);
        }

        $disk = Storage::disk('public');
        $source = $sourceDirectory . $filename;

        if (! $disk->exists($source)) {
            throw new RuntimeException($missingFileMessage);
        }

        $targetFilename = sprintf('%d_%s', $userId, preg_replace('/^\d+_/', '', $filename));
        $target = $targetDirectory . $targetFilename;

        if (! $disk->copy($source, $target)) {
            throw new RuntimeException($copyFailedMessage);
        }

        $disk->delete($source);

        return $targetFilename;
    }

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
