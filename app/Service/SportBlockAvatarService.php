<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;

class SportBlockAvatarService
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
     * Сохраняет аватар спортивного объекта и возвращает имя файла.
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
