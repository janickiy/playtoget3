<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventCoverService
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
     * Сохраняет обложку мероприятия и возвращает имя файла.
     *
     * @param UploadedFile|null $file
     * @return string|null
     */
    public function storeCover(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $filename = $this->images->hashedFilename($file);

        return Storage::disk('public')->putFileAs('images/events/cover_page', $file, $filename)
            ? $filename
            : null;
    }

    /**
     * Удаляет обложку мероприятия из нового и legacy-хранилища.
     *
     * @param string $filename
     * @return void
     */
    public function deleteCover(string $filename): void
    {
        Storage::disk('public')->delete('images/events/cover_page/' . $filename);

        $legacyPath = public_path('uploads/images/events/cover_page/' . $filename);
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
