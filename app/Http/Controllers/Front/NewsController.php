<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{

    /**
     * Показывает ленту новостей пользователя с первой страницей записей.
     *
     * @param NewsRepository $news
     * @return View
     */
    public function index(NewsRepository $news): View
    {
        $pageSize = 5;
        $items = $news->feedPage($pageSize);

        return view('front.news.index', [
            'title' => 'Мои новости',
            'news' => $items,
            'newsPageSize' => $pageSize,
            'newsOffset' => $pageSize,
            'hasMore' => $news->feedPage($pageSize, $pageSize)->isNotEmpty(),
            'newNewsCount' => 0,
        ]);
    }
}
