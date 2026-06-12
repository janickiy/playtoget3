<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CommunityImageService
{
    private ImageFileService $images;

    /**
     * Подключает сервис для генерации имен загруженных изображений.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Сохраняет изображение сообщества и возвращает имя файла.
     *
     * @param UploadedFile|null $file
     * @param string $directory
     * @param string $kind
     * @return string|null
     */
    public function storeCommunityImage(?UploadedFile $file, string $directory, string $kind = 'team'): ?string
    {
        if (! $file) {
            return null;
        }

        $filename = $this->images->hashedFilename($file);

        return Storage::disk('public')->putFileAs('images/' . $kind . 'content/' . $directory, $file, $filename)
            ? $filename
            : null;
    }

    /**
     * Удаляет изображение сообщества из нового и legacy-хранилища.
     *
     * @param string $filename
     * @param string $directory
     * @param string $kind
     * @return void
     */
    public function deleteCommunityImage(string $filename, string $directory, string $kind = 'team'): void
    {
        Storage::disk('public')->delete('images/' . $kind . 'content/' . $directory . '/' . $filename);

        $legacyPath = public_path('uploads/images/' . $kind . 'content/' . $directory . '/' . $filename);
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
