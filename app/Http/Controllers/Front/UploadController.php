<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadController extends Controller
{
    public function image(string $path): BinaryFileResponse
    {
        $path = $this->normalizePath($path);

        foreach ($this->imageRoots() as $root) {
            $file = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));

            if ($file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)) {
                return response()->file($file);
            }
        }

        abort(404);
    }

    private function normalizePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_if($path === '' || str_contains($path, '..'), 404);

        return $path;
    }

    /**
     * Falls back to the legacy project uploads while media are not copied into site3.
     */
    private function imageRoots(): array
    {
        return array_values(array_filter(array_unique([
            realpath(public_path('uploads/images')) ?: null,
            realpath((string) env('LEGACY_UPLOADS_IMAGES_PATH')) ?: null,
            realpath(base_path('../../site5.local/www/uploads/images')) ?: null,
        ])));
    }
}
