<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SportBlockAvatarService
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
     * Сохраняет avatar sport object и возвращает name файла.
     *
     * @param UploadedFile|null $file
     * @return string|null
     */
    public function storeAvatar(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $filename = $this->images->hashedFilename($file);

        return $file->storeAs('images/sportblocks/avatar', $filename, 'public') ? $filename : null;
    }

    /**
     * Deletes avatar sport object из нового и legacy-storage.
     *
     * @param string $filename
     * @return void
     */
    public function deleteAvatar(string $filename): void
    {
        Storage::disk('public')->delete('images/sportblocks/avatar/' . $filename);

        $legacyPath = public_path('uploads/images/sportblocks/avatar/' . $filename);
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
