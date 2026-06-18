<?php

namespace App\Service;

use App\Support\MediaPath;
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

        return $file->storeAs(MediaPath::storage('sport_block_avatar'), $filename, 'public') ? $filename : null;
    }

    /**
     * Deletes avatar sport object из storage и uploads.
     *
     * @param string $filename
     * @return void
     */
    public function deleteAvatar(string $filename): void
    {
        Storage::disk('public')->delete(MediaPath::storage('sport_block_avatar', $filename));

        $uploadsPath = public_path(MediaPath::uploads('sport_block_avatar', $filename));
        if (is_file($uploadsPath)) {
            @unlink($uploadsPath);
        }
    }
}
