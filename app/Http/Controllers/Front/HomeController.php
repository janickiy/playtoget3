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
        $q = $request->query('q');

        return match ($task) {
            'ajax_action' => redirect('/ajax/' . $request->query('action', 'index')),
            'news' => redirect()->route('front.news.index'),
            'profile' => redirect()->route('front.profile.show', ['user' => $request->query('user_id')]),
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
            'events' => $this->redirectLegacyEvents($request, $q),
            'edit_profile' => redirect()->route('front.profile.edit'),
            'friends' => $request->filled('user_id')
                ? redirect()->route('front.friends.user', ['user' => $request->query('user_id')])
                : redirect()->route('front.friends.index'),
            'photoalbums' => $this->redirectLegacyPhotoalbums($request, $q),
            'videoalbums' => $request->filled('user_id')
                ? redirect()->route('front.videoalbums.user', ['user' => $request->query('user_id')])
                : redirect()->route('front.videoalbums.index'),
            'teams' => $this->redirectLegacyTeams($request, $q),
            'content' => redirect()->route('front.content.show', ['content' => $request->query('content_id')]),
            'feedback' => redirect()->route('front.feedback.create'),
            default => redirect()->route('front.news.index'),
        };
    }

    private function redirectLegacyEvents(Request $request, mixed $q): RedirectResponse
    {
        if ($q === 'create') {
            return redirect()->route('front.events.create');
        }

        if (! $request->filled('event_id')) {
            return redirect()->route('front.events.index');
        }

        return match ($q) {
            'members' => redirect()->route('front.events.members', ['event' => $request->query('event_id')]),
            'photoalbums' => redirect()->route('front.events.photoalbums', ['event' => $request->query('event_id')]),
            'videoalbums' => redirect()->route('front.events.videoalbums', ['event' => $request->query('event_id')]),
            default => redirect()->route('front.events.show', ['event' => $request->query('event_id')]),
        };
    }

    private function redirectLegacyPhotoalbums(Request $request, mixed $q): RedirectResponse
    {
        if ($q === 'add_photo') {
            return redirect()->route('front.photoalbums.add-photo');
        }

        if ($q === 'create_photoalbum') {
            return redirect()->route('front.photoalbums.create');
        }

        return $request->filled('user_id')
            ? redirect()->route('front.photoalbums.user', ['user' => $request->query('user_id')])
            : redirect()->route('front.photoalbums.index');
    }

    private function redirectLegacyTeams(Request $request, mixed $q): RedirectResponse
    {
        if ($request->filled('user_id')) {
            return redirect()->route('front.teams.user', ['user' => $request->query('user_id')]);
        }

        $communityId = $request->query('community_id');

        if ($request->filled('photo')) {
            return redirect()->route('front.teams.photoalbums.photo', ['community' => $communityId, 'photo' => $request->query('photo')]);
        }

        if ($q === 'edit_photoalbum' && $request->filled('id_album')) {
            return redirect()->route('front.teams.photoalbum.edit', ['community' => $communityId, 'album' => $request->query('id_album')]);
        }

        return match ($q) {
            'members' => redirect()->route('front.teams.members', ['community' => $communityId]),
            'photoalbums' => redirect()->route('front.teams.photoalbums', ['community' => $communityId]),
            'add_photo' => redirect()->route('front.teams.photoalbums.add-photo', ['community' => $communityId]),
            'videoalbums' => redirect()->route('front.teams.videoalbums', ['community' => $communityId]),
            'add_video' => redirect()->route('front.teams.videoalbums.add-video', ['community' => $communityId]),
            'create_videoalbum' => redirect()->route('front.teams.videoalbums.create', ['community' => $communityId]),
            default => redirect()->route('front.teams.show', ['community' => $communityId]),
        };
    }
}
