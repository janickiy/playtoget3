<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;

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
}
