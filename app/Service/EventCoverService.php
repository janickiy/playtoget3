<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventCoverService
{
    /**
     * @param UploadedFile|null $file
     * @return string|null
     */
    public function storeCover(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return Storage::disk('public')->putFileAs('images/events/cover_page', $file, $filename)
            ? $filename
            : null;
    }

    /**
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
