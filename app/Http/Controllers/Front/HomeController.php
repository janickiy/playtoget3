<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use App\Repositories\SearchRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Shows home page: guests form login, authenticated users news feed.
     *
     * @param Request $request
     * @param NewsRepository $news
     * @param SearchRepository $search
     * @return View
     */
    public function index(Request $request, NewsRepository $news, SearchRepository $search): View
    {
        if ($request->has('search') || $request->has('q')) {
            $query = trim((string) $request->query('search', $request->query('q', '')));
            $results = $search->results($query, Auth::guard('web')->user());
            $total = collect($results)->sum(fn ($items): int => $items->count());

            return view('front.search.index', [
                'title' => 'Search',
                'query' => $query,
                'results' => $results,
                'total' => $total,
            ]);
        }

        if (Auth::guard('web')->check()) {
            $pageSize = 5;
            $items = $news->feedPage($pageSize);

            return view('front.news.index', [
                'title' => 'Home',
                'news' => $items,
                'newsPageSize' => $pageSize,
                'newsOffset' => $pageSize,
                'hasMore' => $news->feedPage($pageSize, $pageSize)->isNotEmpty(),
                'newNewsCount' => 0,
            ]);
        }

        return view('front.auth.login', [
            'title' => 'Sports social network',
            'email' => $request->old('username'),
        ]);
    }
}
