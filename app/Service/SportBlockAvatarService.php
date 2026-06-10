<?php

namespace App\Service;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SportBlockAvatarService
{
    /**
     * @param UploadedFile|null $file
     * @return string|null
     */
    public function storeAvatar(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return $file->storeAs('images/sportblocks/avatar', $filename, 'public') ? $filename : null;
    }
}
