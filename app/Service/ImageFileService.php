<?php

namespace App\Service;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class ImageFileService
{
    /**
     * Возвращает нормализованное расширение загруженного изображения.
     */
    public function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    /**
     * Генерирует имя файла с хешем для пользовательских upload-изображений.
     */
    public function hashedFilename(UploadedFile $file): string
    {
        return Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8)))
            . '.'
            . $this->extension($file);
    }

    /**
     * Генерирует короткое случайное имя файла с расширением исходного изображения.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function randomFilename(UploadedFile $file): string
    {
        return Str::lower(Str::random(32)) . '.' . $this->extension($file);
    }

    /**
     * Генерирует имя изображения профиля с префиксом пользователя.
     *
     * @param UploadedFile $file
     * @param int $userId
     * @return string
     */
    public function userScopedFilename(UploadedFile $file, int $userId): string
    {
        return sprintf('%d_%s.%s', $userId, Str::lower(Str::random(32)), $this->extension($file));
    }

    /**
     * Генерирует имя временного JPG-файла профиля с префиксом пользователя.
     *
     * @param int $userId
     * @return string
     */
    public function temporaryProfileFilename(int $userId): string
    {
        return sprintf('%d_%s.jpg', $userId, Str::lower(Str::random(32)));
    }

    /**
     * Открывает загруженный файл как GD-изображение и учитывает ориентацию JPEG.
     *
     * @param UploadedFile $file
     * @param bool $allowGif
     * @return GdImage
     */
    public function imageResource(UploadedFile $file, bool $allowGif = false): GdImage
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();

        if (! is_string($path)) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => $allowGif ? imagecreatefromgif($path) : false,
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $mime === 'image/jpeg' || $mime === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    /**
     * Поворачивает JPEG-изображение по EXIF-ориентации, если она задана.
     *
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
