<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Показывает главную страницу: гостям форму входа, авторизованным пользователям ленту новостей.
     *
     * @param Request $request
     * @param NewsRepository $news
     * @return View
     */
    public function index(Request $request, NewsRepository $news): View
    {
        if (Auth::guard('web')->check()) {
            $pageSize = 5;
            $items = $news->feedPage($pageSize);

            return view('front.news.index', [
                'title' => 'Главная',
                'news' => $items,
                'newsPageSize' => $pageSize,
                'newsOffset' => $pageSize,
                'hasMore' => $news->feedPage($pageSize, $pageSize)->isNotEmpty(),
                'newNewsCount' => 0,
            ]);
        }

        return view('front.auth.login', [
            'title' => 'Спортивный интернет-проект',
            'email' => $request->old('username'),
        ]);
    }
}
