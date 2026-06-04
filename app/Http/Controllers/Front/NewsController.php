<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    public function index(NewsRepository $news): View
    {
        return view('front.news.index', [
            'title' => 'Новости',
            'news' => $news->latest(),
        ]);
    }
}
