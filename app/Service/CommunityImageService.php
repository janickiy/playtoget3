<?php

namespace App\Service;

use App\Support\MediaPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CommunityImageService
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
     * Saves the image community and returns the file name.
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

        return Storage::disk('public')->putFileAs(MediaPath::community($kind, $directory), $file, $filename)
            ? $filename
            : null;
    }

    /**
     * Deletes the community image from storage and uploads.
     *
     * @param string $filename
     * @param string $directory
     * @param string $kind
     * @return void
     */
    public function deleteCommunityImage(string $filename, string $directory, string $kind = 'team'): void
    {
        Storage::disk('public')->delete(MediaPath::community($kind, $directory, $filename));

        $uploadsPath = public_path(MediaPath::communityUploads($kind, $directory, $filename));
        if (is_file($uploadsPath)) {
            @unlink($uploadsPath);
        }
    }
}
