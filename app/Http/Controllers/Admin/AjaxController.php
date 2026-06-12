<?php

namespace App\Http\Controllers\Admin;

use App\Models\Content;
use App\Helpers\StringHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AjaxController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->input('action')) {
            switch ($request->input('action')) {
                case 'get_page_slug':
                    $baseSlug = trim(StringHelper::slug(trim((string) $request->input('title'))), '-');
                    $baseSlug = $baseSlug !== '' ? $baseSlug : 'page';
                    $slug = $baseSlug;
                    $contentId = (int) $request->input('id', 0);
                    $index = 2;

                    while ($this->contentSlugExists($slug, $contentId)) {
                        $slug = $baseSlug . '-' . $index;
                        $index++;
                    }

                    return response()->json(['slug' => $slug]);
            }
        }

        return response()->json([]);
    }

    private function contentSlugExists(string $slug, int $contentId = 0): bool
    {
        $query = Content::query()->where('slug', $slug);

        if ($contentId > 0) {
            $query->whereKeyNot($contentId);
        }

        return $query->exists();
    }
}
