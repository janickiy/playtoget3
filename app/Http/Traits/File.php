<?php

namespace App\Http\Traits;

use Storage;

trait File
{
    /**
     * @param string|null $file
     * @param string $path
     * @return bool
     */
    public static function deleteFile(?string $file, string $path): bool
    {
        if (empty($file)) {
            return false;
        }

        $filePath = sprintf('%s/%s', $path, $file);

        if (Storage::disk('public')->exists($filePath) === true) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }

    /**
     * @param string|null $file
     * @param string $path
     * @return string|null
     */
    public static function getFile(?string $file = null, string $path): ?string
    {
        return $file ? Storage::disk('public')->url(sprintf('%s/%s', $path, $file)) : null;
    }
}
