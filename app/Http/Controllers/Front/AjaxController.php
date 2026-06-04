<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function __construct(private readonly NewsRepository $news)
    {
    }

    public function handle(Request $request, string $action): JsonResponse
    {
        return match ($action) {
            'get_usernews_list' => $this->getUserNewsList($request),
            default => response()->json([
                'action' => $action,
                'status' => 'not_implemented',
                'payload' => $request->except(['_token']),
            ]),
        };
    }

    private function getUserNewsList(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('number', 5), 1), 25);
        $offset = max((int) $request->input('offset', 0), 0);
        $news = $this->news->feedPage($limit, $offset);
        $hasMore = $this->news->feedPage($limit, $offset + $limit)->isNotEmpty();

        return response()->json([
            'status' => 1,
            'html' => view('front.news._items', ['news' => $news])->render(),
            'count' => $news->count(),
            'has_more' => $hasMore,
        ]);
    }
}
