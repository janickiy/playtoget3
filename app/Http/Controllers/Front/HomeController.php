<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
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

    private function redirectLegacyTask(Request $request): RedirectResponse
    {
        $task = (string) $request->query('task', 'news');
        $actions = $this->legacyQueryValues($request, 'q');

        return match ($task) {
            'ajax_action' => redirect('/ajax/' . $request->query('action', 'index')),
            'news' => redirect()->route('front.news.index'),
            'profile' => $this->redirectLegacyProfile($request, $actions),
            'playgrounds' => $request->filled('id_sport_block')
                ? redirect()->route('front.playgrounds.index', ['sportBlock' => $request->query('id_sport_block')])
                : redirect()->route('front.playgrounds.index'),
            'shops' => $request->filled('id_sport_block')
                ? redirect()->route('front.shops.index', ['sportBlock' => $request->query('id_sport_block')])
                : redirect()->route('front.shops.index'),
            'fitness' => $request->filled('id_sport_block')
                ? redirect()->route('front.fitness.index', ['sportBlock' => $request->query('id_sport_block')])
                : redirect()->route('front.fitness.index'),
            'calendar' => redirect()->route('front.calendar.index'),
            'events' => $this->redirectLegacyEvents($request, $actions),
            'edit_profile' => redirect()->route('front.profile.edit'),
            'friends' => $request->filled('user_id')
                ? redirect()->route('front.friends.user', ['user' => $request->query('user_id')])
                : redirect()->route('front.friends.index'),
            'photoalbums' => $this->redirectLegacyPhotoalbums($request, $actions),
            'videoalbums' => $request->filled('user_id')
                ? redirect()->route('front.videoalbums.user', ['user' => $request->query('user_id')])
                : redirect()->route('front.videoalbums.index'),
            'teams' => $this->redirectLegacyTeams($request, $actions),
            'content' => redirect()->route('front.content.show', ['content' => $request->query('content_id')]),
            'feedback' => redirect()->route('front.feedback.create'),
            default => redirect()->route('front.news.index'),
        };
    }

    private function redirectLegacyEvents(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'create')) {
            return redirect()->route('front.events.create');
        }

        if (! $request->filled('event_id')) {
            return redirect()->route('front.events.index');
        }

        if ($this->hasLegacyAction($actions, 'members')) {
            return redirect()->route('front.events.members', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'photoalbums')) {
            return redirect()->route('front.events.photoalbums', ['event' => $request->query('event_id')]);
        }

        if ($this->hasLegacyAction($actions, 'videoalbums')) {
            return redirect()->route('front.events.videoalbums', ['event' => $request->query('event_id')]);
        }

        return redirect()->route('front.events.show', ['event' => $request->query('event_id')]);
    }

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

    private function redirectLegacyPhotoalbums(Request $request, array $actions): RedirectResponse
    {
        if ($this->hasLegacyAction($actions, 'add_photo')) {
            return redirect()->route('front.photoalbums.add-photo');
        }

        if ($this->hasLegacyAction($actions, 'create_photoalbum')) {
            return redirect()->route('front.photoalbums.create');
        }

        return $request->filled('user_id')
            ? redirect()->route('front.photoalbums.user', ['user' => $request->query('user_id')])
            : redirect()->route('front.photoalbums.index');
    }

    private function redirectLegacyTeams(Request $request, array $actions): RedirectResponse
    {
        if ($request->filled('user_id')) {
            return redirect()->route('front.teams.user', ['user' => $request->query('user_id')]);
        }

        $communityId = $request->query('community_id');

        if ($request->filled('photo')) {
            return redirect()->route('front.teams.photoalbums.photo', ['community' => $communityId, 'photo' => $request->query('photo')]);
        }

        if ($this->hasLegacyAction($actions, 'edit_photoalbum') && $request->filled('id_album')) {
            return redirect()->route('front.teams.photoalbum.edit', ['community' => $communityId, 'album' => $request->query('id_album')]);
        }

        if ($this->hasLegacyAction($actions, 'members')) {
            return redirect()->route('front.teams.members', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'add_photo')) {
            return redirect()->route('front.teams.photoalbums.add-photo', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'photoalbums')) {
            return redirect()->route('front.teams.photoalbums', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'add_video')) {
            return redirect()->route('front.teams.videoalbums.add-video', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'create_videoalbum')) {
            return redirect()->route('front.teams.videoalbums.create', ['community' => $communityId]);
        }

        if ($this->hasLegacyAction($actions, 'videoalbums')) {
            return redirect()->route('front.teams.videoalbums', ['community' => $communityId]);
        }

        return redirect()->route('front.teams.show', ['community' => $communityId]);
    }

    private function hasLegacyAction(array $actions, string $action): bool
    {
        return in_array($action, $actions, true);
    }

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
