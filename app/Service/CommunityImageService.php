<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityImageService
{
    /**
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

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return Storage::disk('public')->putFileAs('images/' . $kind . 'content/' . $directory, $file, $filename)
            ? $filename
            : null;
    }

    /**
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
