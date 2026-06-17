<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CommunityImageService
{
    private ImageFileService $images;

    /**
     * Connects сервис для генерации имен загруженных images.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Сохраняет image community и возвращает name файла.
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
     * Deletes image community из нового и legacy-storage.
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
