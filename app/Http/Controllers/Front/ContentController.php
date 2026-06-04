<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\ContentRepository;
use Illuminate\Contracts\View\View;

class ContentController extends Controller
{
    public function show(int $content, ContentRepository $pages): View
    {
        return view('front.content.show', [
            'page' => $pages->visible($content),
        ]);
    }
}
