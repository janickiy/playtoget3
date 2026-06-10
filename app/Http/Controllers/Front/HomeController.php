<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Показывает главную страницу или перенаправляет legacy-запросы по параметру task
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->has('task')) {
            return $this->redirectLegacyTask($request);
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('front.news.index');
        }

        return view('front.auth.login', [
            'title' => 'Спортивный интернет-проект',
            'email' => $request->old('username'),
        ]);
    }


    /**
     * Определяет старый task/action и перенаправляет его на новый фронтовый маршрут.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    private function redirectLegacyTask(Request $request): RedirectResponse
    {
        $task = (string) $request->query('task', 'news');
        $actions = $this->legacyQueryValues($request, 'q');

        return match ($task) {
            'ajax_action' => redirect('/ajax/' . $request->query('action', 'index')),
            'news' => redirect()->route('front.news.index'),
            'profile' => $this->redirectLegacyProfile($request, $actions),
            'playgrounds' => $this->redirectLegacySportBlocks($request, $actions, 'front.playgrounds'),
            'shops' => $this->redirectLegacySportBlocks($request, $actions, 'front.shops'),
            'fitness' => $this->redirectLegacySportBlocks($request, $actions, 'front.fitness'),
            'calendar' => redirect()->route('front.calendar.index'),
            'events' => $this->redirectLegacyEvents($request, $actions),
            'edit_profile' => redirect()->route('front.profile.edit'),
            'friends' => $request->filled('user_id')
                ? redirect()->route('front.friends.user', ['user' => $request->query('user_id')])
                : redirect()->route('front.friends.index'),
            'photoalbums' => $this->redirectLegacyPhotoalbums($request, $actions),
            'videoalbums' => $this->redirectLegacyVideoalbums($request, $actions),
            'teams' => $this->redirectLegacyTeams($request, $actions),
            'groups' => $this->redirectLegacyGroups($request, $actions),
            'content' => redirect()->route('front.content.show', ['content' => $request->query('content_id')]),
            'feedback' => redirect()->route('front.feedback.create'),
            default => redirect()->route('front.news.index'),
        };
    }

    /**
     * Преобразует legacy-ссылки мероприятий в новые маршруты мероприятий.
     *
     * @param Request $request
     * @param array $actions
     * @return RedirectResponse
     */
    private function redirectLegacyEvents(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'create')) {
            return redirect()->route('front.events.create');
        }

        if (! $request->filled('event_id')) {
            return redirect()->route('front.events.index');
        }

        if ($this->hasLegacyAction($actions, 'edit')) {
            return redirect()->route('front.events.edit', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'members')) {
            return redirect()->route('front.events.members', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'add_photo')) {
            return redirect()->route('front.events.photoalbums.add-photo', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'create_photoalbum')) {
            return redirect()->route('front.events.photoalbums.create', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'photoalbums')) {
            if ($request->filled('id_album')) {
                return redirect()->route('front.events.photoalbums.show', [
                    'event' => $request->query('event_id'),
                    'album' => $request->query('id_album'),
                ]);
            }

            return redirect()->route('front.events.photoalbums', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'add_video')) {
            return redirect()->route('front.events.videoalbums.add-video', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'create_videoalbum')) {
            return redirect()->route('front.events.videoalbums.create', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'videoalbums')) {
            if ($request->filled('id_album')) {
                return redirect()->route('front.events.videoalbums.show', [
                    'event' => $request->query('event_id'),
                    'album' => $request->query('id_album'),
                ]);
            }

            return redirect()->route('front.events.videoalbums', ['event' => $request->query('event_id')]);
        }

        return redirect()->route('front.events.show', ['event' => $request->query('event_id')]);
    }

    /**
     * Преобразует legacy-ссылки спорт-блоков в новые маршруты площадок, магазинов или фитнеса.
     *
     * @param Request $request
     * @param array $actions
     * @param string $routePrefix
     * @return RedirectResponse
     */
    private function redirectLegacySportBlocks(Request $request, array $actions, string $routePrefix): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'create')) {
            return redirect()->route($routePrefix . '.create');
        }

        if (! $request->filled('id_sport_block')) {
            return redirect()->route($routePrefix . '.index');
        }

        if ($this->hasLegacyAction($actions, 'edit')) {
            return redirect()->route($routePrefix . '.edit', ['sportBlock' => $request->query('id_sport_block')]);
        }

        return redirect()->route($routePrefix . '.index', ['sportBlock' => $request->query('id_sport_block')]);
    }

    /**
     * Преобразует legacy-ссылки групп в новые маршруты групп.
     *
     * @param Request $request
     * @param array $actions
     * @return RedirectResponse
     */
    private function redirectLegacyGroups(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'create')) {
            return redirect()->route('front.groups.create');
        }

        if (! $request->filled('community_id')) {
            return redirect()->route('front.groups.index');
        }

        if ($this->hasLegacyAction($actions, 'members')) {
            return redirect()->route('front.groups.members', ['community' => $request->query('community_id')]);
        }

        if ($this->hasLegacyAction($actions, 'edit')) {
            return redirect()->route('front.groups.edit', ['community' => $request->query('community_id')]);
        }

        return redirect()->route('front.groups.show', ['community' => $request->query('community_id')]);
    }

    /**
     * Преобразует legacy-ссылки профиля, друзей и сообщений в новые маршруты.
     *
     * @param Request $request
     * @param array $actions
     * @return RedirectResponse
     */
    private function redirectLegacyProfile(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'messages') && $request->filled('sel')) {
            return redirect()->route('front.profile.messages.show', [
                'user' => $request->query('user_id'),
                'recipient' => $request->query('sel'),
            ]);
        }

        if ($this->hasLegacyAction($actions, 'dialogues')) {
            return redirect()->route('front.profile.messages.index', ['user' => $request->query('user_id')]);
        }

        return redirect()->route('front.profile.show', ['user' => $request->query('user_id')]);
    }

    /**
     * Преобразует legacy-ссылки фотоальбомов в новые маршруты фотоальбомов.
     *
     * @param Request $request
     * @param array $actions
     * @return RedirectResponse
     */
    private function redirectLegacyPhotoalbums(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'add_photo')) {
            return redirect()->route('front.photoalbums.add-photo');
        }

        if ($this->hasLegacyAction($actions, 'create_photoalbum')) {
            return redirect()->route('front.photoalbums.create');
        }

        if ($this->hasLegacyAction($actions, 'edit_photoalbum') && $request->filled('id_album')) {
            return redirect()->route('front.photoalbums.edit', ['album' => $request->query('id_album')]);
        }

        if ($request->filled('id_album')) {
            return redirect()->route('front.photoalbums.show', ['album' => $request->query('id_album')]);
        }

        return $request->filled('user_id')
            ? redirect()->route('front.photoalbums.user', ['user' => $request->query('user_id')])
            : redirect()->route('front.photoalbums.index');
    }

    /**
     * Преобразует legacy-ссылки видеоальбомов в новые маршруты видеоальбомов.
     *
     * @param Request $request
     * @param array $actions
     * @return RedirectResponse
     */
    private function redirectLegacyVideoalbums(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'add_video')) {
            return redirect()->route('front.videoalbums.add-video');
        }

        if ($this->hasLegacyAction($actions, 'create_videoalbum')) {
            return redirect()->route('front.videoalbums.create');
        }

        if ($this->hasLegacyAction($actions, 'edit_videoalbum') && $request->filled('id_album')) {
            return redirect()->route('front.videoalbums.edit', ['album' => $request->query('id_album')]);
        }

        if ($request->filled('id_album')) {
            return redirect()->route('front.videoalbums.show', ['album' => $request->query('id_album')]);
        }

        return $request->filled('user_id')
            ? redirect()->route('front.videoalbums.user', ['user' => $request->query('user_id')])
            : redirect()->route('front.videoalbums.index');
    }

    /**
     * Преобразует legacy-ссылки команд в новые маршруты команд.
     */
    private function redirectLegacyTeams(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'create')) {
            return redirect()->route('front.teams.create');
        }

        if ($request->filled('user_id')) {
            return redirect()->route('front.teams.user', ['user' => $request->query('user_id')]);
        }

        $communityId = $request->query('community_id');

        if ($request->filled('photo') && $request->filled('id_album')) {
            return redirect()->route('front.teams.photoalbums.photo', [
                'community' => $communityId,
                'album' => $request->query('id_album'),
                'photo' => $request->query('photo'),
            ]);
        }

        if ($request->filled('photo')) {
            return redirect()->route('front.teams.photoalbums.photo.legacy', [
                'community' => $communityId,
                'photo' => $request->query('photo'),
            ]);
        }

        if ($this->hasLegacyAction($actions, 'edit_photoalbum') && $request->filled('id_album')) {
            return $communityId
                ? redirect()->route('front.teams.photoalbum.edit.with-community', [
                    'community' => $communityId,
                    'album' => $request->query('id_album'),
                ])
                : redirect()->route('front.teams.photoalbum.edit', ['album' => $request->query('id_album')]);
        }

        if ($this->hasLegacyAction($actions, 'edit_videoalbum') && $request->filled('id_album')) {
            return redirect()->route('front.teams.videoalbum.edit', ['album' => $request->query('id_album')]);
        }

        if ($this->hasLegacyAction($actions, 'edit')) {
            return redirect()->route('front.teams.edit', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'members')) {
            return redirect()->route('front.teams.members', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'add_photo')) {
            return redirect()->route('front.teams.photoalbums.add-photo', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'create_photoalbum')) {
            return redirect()->route('front.teams.photoalbums.create', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'photoalbums')) {
            if ($request->filled('id_album')) {
                return redirect()->route('front.teams.photoalbums.show', [
                    'community' => $communityId,
                    'album' => $request->query('id_album'),
                ]);
            }

            return redirect()->route('front.teams.photoalbums', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'add_video')) {
            return redirect()->route('front.teams.videoalbums.add-video', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'create_videoalbum')) {
            return redirect()->route('front.teams.videoalbums.create', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'videoalbums')) {
            if ($request->filled('id_album')) {
                return redirect()->route('front.teams.videoalbums.show', [
                    'community' => $communityId,
                    'album' => $request->query('id_album'),
                ]);
            }

            return redirect()->route('front.teams.videoalbums', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'events')) {
            return redirect()->route('front.teams.events', ['community' => $communityId]);
        }

        if (! $communityId) {
            return redirect()->route('front.teams.index');
        }

        return redirect()->route('front.teams.show', ['community' => $communityId]);
    }

    /**
     * Проверяет наличие legacy-action в нормализованном списке действий.
     */
    private function hasLegacyAction(array $actions, string $action): bool
    {
        return in_array($action, $actions, true);
    }

    /**
     * Возвращает все значения legacy query-параметра с учетом дублей в URL.
     */
    private function legacyQueryValues(Request $request, string $key): array
    {
        $values = [];
        $queryString = (string) $request->server->get('QUERY_STRING', '');

        foreach (explode('&', $queryString) as $part) {
            if ($part === '') {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $part, 2), 2, '');

            if (urldecode($name) === $key) {
                $values[] = urldecode($value);
            }
        }

        $queryValue = $request->query($key);

        if (is_array($queryValue)) {
            $values = array_merge($values, $queryValue);
        } elseif ($queryValue !== null) {
            $values[] = (string) $queryValue;
        }

        return array_values(array_unique(array_filter($values, fn (string $value): bool => $value !== '')));
    }
}
