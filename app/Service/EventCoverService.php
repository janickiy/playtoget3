<?php

namespace App\Service;

use App\Support\MediaPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventCoverService
{
    private ImageFileService $images;

    /**
     * Connects service for generating names of downloaded images.
     */
    public function __construct(?ImageFileService $images = null)
    {
        $this->images = $images ?? new ImageFileService();
    }

    /**
     * Saves the cover event and returns the file name.
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

        return Storage::disk('public')->putFileAs(MediaPath::storage('event_cover'), $file, $filename)
            ? $filename
            : null;
    }

    /**
     * Deletes cover event из storage и uploads.
     *
     * @param string $filename
     * @return void
     */
    public function deleteCover(string $filename): void
    {
        Storage::disk('public')->delete(MediaPath::storage('event_cover', $filename));

        $uploadsPath = public_path(MediaPath::uploads('event_cover', $filename));
        if (is_file($uploadsPath)) {
            @unlink($uploadsPath);
        }
    }
}
