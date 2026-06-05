<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\GeoCity;
use App\Models\SportType;
use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\NewsRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AjaxController extends Controller
{
    public function __construct(
        private readonly NewsRepository $news,
        private readonly FriendRepository $friends,
        private readonly UserRepository $users,
    ) {
    }

    public function handle(Request $request, string $action): JsonResponse
    {
        return match ($action) {
            'get_usernews_list' => $this->getUserNewsList($request),
            'getpossiblefriends' => $this->getPossibleFriends($request),
            'get_friends_list' => $this->getFriendsList($request),
            'add_as_friend' => $this->addAsFriend($request),
            'accept_friendship' => $this->acceptFriendship($request),
            'remove_friend' => $this->removeFriend($request),
            'search_city' => $this->searchCity($request),
            'search_sport_types' => $this->searchSportTypes($request),
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

    private function getPossibleFriends(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (! $viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int) $request->input('number', 6), 1), 24);
        $users = $this->friends->possibleFriendsFor($viewer->id, $limit);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function getFriendsList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (! $viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int) $request->input('number', 10), 1), 24);
        $offset = max((int) $request->input('offset', 0), 0);
        $userId = (int) $request->input('user_id', $viewer->id);
        $users = $this->friends->friendsFor($userId, $limit, $offset);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function addAsFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1 || ! $this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->requestFriendship($viewer->id, $friendId),
        ]);
    }

    private function acceptFriendship(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1 || ! $this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->acceptFriendship($viewer->id, $friendId),
        ]);
    }

    private function removeFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1) {
            return response()->json(['status' => null, 'result' => ''], 422);
        }

        $removed = $this->friends->removeFriendship($viewer->id, $friendId);

        return response()->json([
            'status' => $removed ? 'success' : null,
            'result' => $removed ? 'success' : '',
        ]);
    }

    private function searchCity(Request $request): JsonResponse
    {
        $city = trim((string) $request->query('city', ''));

        if ($city === '') {
            return response()->json(['item' => []]);
        }

        $items = GeoCity::query()
            ->where(function ($query) use ($city): void {
                $query
                    ->where('name_ru', 'like', '%' . $city . '%')
                    ->orWhere('name_en', 'like', '%' . $city . '%');
            })
            ->orderBy('sort')
            ->orderBy('name_ru')
            ->limit(10)
            ->get(['id', 'name_ru'])
            ->map(fn (GeoCity $city): array => [
                'id' => $city->id,
                'name' => $city->name_ru,
            ]);

        return response()->json(['item' => $items]);
    }

    private function searchSportTypes(Request $request): JsonResponse
    {
        $sportTypes = trim((string) $request->query('sport_types', ''));

        if ($sportTypes === '') {
            return response()->json(['item' => []]);
        }

        $items = SportType::query()
            ->where('name', 'like', '%' . $sportTypes . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (SportType $sportType): array => [
                'id' => $sportType->id,
                'name' => $sportType->name,
            ]);

        return response()->json(['item' => $items]);
    }

    private function viewer(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return $user;
    }
}
