<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\User;
use App\Repositories\CommunityRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class GroupsController extends Controller
{
    public function index(CommunityRepository $communities): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        return view('front.groups.index', [
            'title' => 'Группы',
            'myGroups' => $this->groupsForViewer($communities->myGroups($viewer->id), $communities, $viewer),
            'popularGroups' => $this->groupsForViewer($communities->popularGroups(), $communities, $viewer),
            'invitedGroups' => $this->groupsForViewer($communities->invitedGroups($viewer->id), $communities, $viewer),
            'viewer' => $viewer,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        return view('front.groups.form', [
            'title' => 'Создание группы',
            'action' => route('front.groups.store'),
            'button' => 'Создать группу',
            'group' => null,
            'settings' => null,
            'canEditSettings' => false,
        ]);
    }

    public function store(Request $request, CommunityRepository $communities): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $group = $communities->createGroup($viewer, $this->validateGroup($request, $communities));

        return redirect()->route('front.groups.show', ['community' => $group->id]);
    }

    public function show(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);

        return view('front.groups.members', $this->groupPayload($group, $communities, 'members') + [
            'members' => $communities->members($group->id),
            'applications' => $communities->canManage($group, Auth::guard('web')->user())
                ? $communities->applications($group->id)
                : collect(),
        ]);
    }

    public function members(int $community, CommunityRepository $communities): View
    {
        return $this->show($community, $communities);
    }

    public function edit(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->isOwner($group, $viewer), 403);

        return view('front.groups.form', $this->groupPayload($group, $communities, 'edit') + [
            'title' => 'Редактирование группы',
            'action' => route('front.groups.update', ['community' => $group->id]),
            'button' => 'Сохранить',
            'group' => $group,
            'settings' => $communities->settings($group),
            'canEditSettings' => true,
            'admins' => $communities->admins($group->id),
            'blocked' => $communities->blocked($group->id),
        ]);
    }

    public function update(int $community, Request $request, CommunityRepository $communities): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->isOwner($group, $viewer), 403);

        $communities->updateGroup($group, $this->validateGroup($request, $communities, true));

        return redirect()->route('front.groups.show', ['community' => $group->id]);
    }

    private function groupPayload(Community $group, CommunityRepository $communities, string $section): array
    {
        $viewer = Auth::guard('web')->user();

        return [
            'title' => $group->name ?: 'Группа',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'group' => $group,
            'groupData' => $communities->serializeGroup($group),
            'permissions' => $communities->permissions($group, $viewer),
            'role' => $communities->role($group->id, $viewer?->id),
            'canManageGroup' => $communities->canManage($group, $viewer),
            'section' => $section,
        ];
    }

    private function groupsForViewer(Collection $groups, CommunityRepository $communities, ?User $viewer): Collection
    {
        return $groups->map(function (array $group) use ($communities, $viewer): array {
            $role = $communities->role((int) $group['id'], $viewer?->id);

            $group['status'] = $communities->roleLabel($role);
            $group['can_edit'] = $role === 1;

            return $group;
        });
    }

    private function validateGroup(Request $request, CommunityRepository $communities, bool $withSettings = false): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'id_place' => ['nullable', 'integer'],
            'id_sport' => ['nullable', 'integer'],
            'place' => ['nullable', 'string', 'max:255'],
            'sport' => ['nullable', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:8192'],
            'community.permission_wall' => ['nullable', 'integer', 'min:0', 'max:3'],
            'community.permission_photo' => ['nullable', 'integer', 'min:0', 'max:2'],
            'community.permission_video' => ['nullable', 'integer', 'min:0', 'max:2'],
            'community.type' => ['nullable', 'integer', 'min:0', 'max:2'],
        ], [
            'name.required' => 'Укажите название группы.',
        ]);

        $cityId = (int) ($validated['id_place'] ?? 0);
        $sportId = (int) ($validated['id_sport'] ?? 0);
        $settings = $validated['community'] ?? [];

        return [
            'name' => trim($validated['name']),
            'about' => trim((string) ($validated['about'] ?? '')),
            'city_id' => $cityId,
            'sport_id' => $sportId,
            'place' => trim((string) ($validated['place'] ?? '')) ?: $communities->cityName($cityId),
            'sport_type' => trim((string) ($validated['sport'] ?? '')) ?: $communities->sportName($sportId),
            'permission_wall' => $withSettings ? (int) ($settings['permission_wall'] ?? 0) : 0,
            'permission_photo' => $withSettings ? (int) ($settings['permission_photo'] ?? 0) : 0,
            'permission_video' => $withSettings ? (int) ($settings['permission_video'] ?? 0) : 0,
            'type' => $withSettings ? (int) ($settings['type'] ?? 0) : 0,
            'avatar_file' => $request->file('avatar_file'),
            'cover_file' => $request->file('cover_file'),
        ];
    }

    private function groupOrFail(int $community, CommunityRepository $communities): Community
    {
        $group = $communities->findGroup($community);

        abort_if(! $group, 404);

        return $group;
    }
}
