<?php

namespace App\Http\Controllers\Front;

use App\Helpers\FrontAssets;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendsController extends Controller
{
    /**
     * Подключает репозиторий друзей для всех действий контроллера.
     */
    public function __construct(
        private readonly FriendRepository $friends,
        private readonly UserRepository   $users,
    )
    {
    }

    /**
     * Показывает страницу друзей текущего пользователя.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return view('front.friends.index', $this->pageData($request, $viewer, $viewer, true));
    }

    /**
     * Показывает страницу друзей выбранного пользователя.
     *
     * @param int $user
     * @param Request $request
     * @return View
     */
    public function user(int $user, Request $request): View
    {
        $targetUser = $this->users->findActive($user);
        abort_if(!$targetUser, 404);

        return view('front.friends.index', $this->pageData($request, $this->viewer(), $targetUser, false));
    }

    /**
     * Готовит списки друзей, заявок, рекомендаций и данные профиля для страницы друзей.
     *
     * @param Request $request
     * @param User|null $viewer
     * @param User $targetUser
     * @param bool $isOwnPage
     * @return array
     */
    private function pageData(Request $request, ?User $viewer, User $targetUser, bool $isOwnPage): array
    {
        $filters = $this->filters($request);
        $friendsCount = $this->friends->friendsCountFor($targetUser->id, $filters);

        return [
            'title' => $isOwnPage ? 'Друзья' : 'Друзья пользователя',
            'viewer' => $viewer,
            'targetUser' => $targetUser,
            'isOwnPage' => $isOwnPage && $viewer?->is($targetUser),
            'filters' => $filters,
            'searchRoute' => $isOwnPage
                ? route('front.friends.index')
                : route('front.friends.user', ['user' => $targetUser->id]),
            'profileLayout' => $this->profileLayout($targetUser),
            'possibleFriends' => $isOwnPage
                ? $this->friends->possibleFriendsFor($targetUser->id, 6, $filters)
                : collect(),
            'friends' => $this->friends->friendsFor($targetUser->id, 10, 0, $filters),
            'friendsCount' => $friendsCount,
            'hasMoreFriends' => $friendsCount > 10,
            'incomingRequests' => $isOwnPage
                ? $this->friends->incomingRequestsFor($targetUser->id, 10, 0, $filters)
                : collect(),
            'incomingRequestsCount' => $isOwnPage
                ? $this->friends->incomingRequestsCountFor($targetUser->id, $filters)
                : 0,
            'outgoingRequests' => $isOwnPage
                ? $this->friends->outgoingRequestsFor($targetUser->id, 10, 0, $filters)
                : collect(),
            'outgoingRequestsCount' => $isOwnPage
                ? $this->friends->outgoingRequestsCountFor($targetUser->id, $filters)
                : 0,
        ];
    }

    /**
     * Собирает фильтры поиска друзей из query-параметров.
     *
     * @param Request $request
     * @return array
     */
    private function filters(Request $request): array
    {
        $sex = (string)$request->query('sex', '');
        $minAge = (int)$request->query('min_age', 0);
        $maxAge = (int)$request->query('max_age', 0);

        return [
            'search' => trim((string)$request->query('search', '')),
            'sex' => in_array($sex, ['male', 'female'], true) ? $sex : '',
            'city' => trim((string)$request->query('city', $request->query('place', ''))),
            'sport' => trim((string)$request->query('sport', '')),
            'min_age' => $minAge > 0 ? min($minAge, 99) : null,
            'max_age' => $maxAge > 0 ? min($maxAge, 99) : null,
        ];
    }

    /**
     * Возвращает текущего авторизованного пользователя фронта.
     *
     * @return User|null
     */
    private function viewer(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return $user;
    }

    /**
     * Готовит данные верхнего блока профиля для страниц друзей.
     *
     * @param User $user
     * @return array
     */
    private function profileLayout(User $user): array
    {
        return [
            'user' => $user,
            'firstname' => $user->firstname ?: $user->displayName(),
            'lastname' => $user->firstname ? (string)$user->lastname : '',
            'about' => $user->about ?: '',
            'avatar' => FrontAssets::userAvatar($user),
            'cover' => FrontAssets::userCover($user),
        ];
    }
}
