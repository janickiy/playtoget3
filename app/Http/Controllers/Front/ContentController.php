<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\ContentRepository;
use Illuminate\Contracts\View\View;

class ContentController extends Controller
{
    /**
     * Показывает доступную статическую страницу контента.
     *
     * @param string $slug
     * @param ContentRepository $pages
     * @return View
     */
    public function show(string $slug, ContentRepository $pages): View
    {
        return view('front.content.show', [
            'page' => $pages->visibleBySlug($slug),
        ]);
    }
}
