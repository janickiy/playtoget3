<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Photoalbum;
use App\Models\User;
use App\Models\Videoalbum;
use App\Repositories\CommunityRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class GroupsController extends Controller
{
    private const PAGE_SIZE = 5;
    private const PHOTOS_LIMIT = 6;
    private const ALBUM_PHOTOS_LIMIT = 9;
    private const VIDEOS_LIMIT = 6;
    private const COMMENTS_LIMIT = 10;

    public function index(Request $request, CommunityRepository $communities): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $filters = $this->groupFilters($request);

        return view('front.groups.index', [
            'title' => 'Группы',
            'myGroups' => $this->groupsForViewer($communities->myGroups($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'popularGroups' => $this->groupsForViewer($communities->popularGroups(self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'invitedGroups' => $this->groupsForViewer($communities->invitedGroups($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'myGroupsTotal' => $communities->myGroupsCount($viewer->id, $filters),
            'popularGroupsTotal' => $communities->popularGroupsCount($filters),
            'invitedGroupsTotal' => $communities->invitedGroupsCount($viewer->id, $filters),
            'groupsPageSize' => self::PAGE_SIZE,
            'viewer' => $viewer,
        ]);
    }

    public function user(int $user, CommunityRepository $communities): View
    {
        return view('front.groups.index', [
            'title' => 'Группы пользователя',
            'myGroups' => $this->groupsForViewer($communities->myGroups($user, 20), $communities, Auth::guard('web')->user()),
            'popularGroups' => collect(),
            'invitedGroups' => collect(),
            'viewer' => Auth::guard('web')->user(),
            'viewedUserId' => $user,
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

    public function show(int $community, CommunityRepository $communities, ProfileRepository $profiles): View
    {
        $group = $this->groupOrFail($community, $communities);
        $payload = $this->groupPayload($group, $communities, 'feed');

        return view('front.teams.feed', $payload + [
            'comments' => $payload['permissions']['wall']
                ? $profiles->comments('group', $group->id, self::COMMENTS_LIMIT, 0, Auth::guard('web')->user())
                : collect(),
            'commentsPageSize' => self::COMMENTS_LIMIT,
            'hasMoreComments' => $payload['permissions']['wall']
                ? $profiles->hasMoreComments('group', $group->id, self::COMMENTS_LIMIT, 0)
                : false,
        ]);
    }

    public function members(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);

        return view('front.groups.members', $this->groupPayload($group, $communities, 'members') + [
            'members' => $communities->members($group->id),
            'applications' => $communities->canManage($group, Auth::guard('web')->user())
                ? $communities->applications($group->id)
                : collect(),
        ]);
    }

    public function edit(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($group, $viewer), 403);

        return view('front.groups.form', array_merge($this->groupPayload($group, $communities, 'edit'), [
            'title' => 'Редактирование группы',
            'action' => route('front.groups.update', ['community' => $group->id]),
            'button' => 'Сохранить',
            'group' => $group,
            'settings' => $communities->settings($group),
            'canEditSettings' => true,
            'admins' => $communities->admins($group->id),
            'blocked' => $communities->blocked($group->id),
        ]));
    }

    public function update(int $community, Request $request, CommunityRepository $communities): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($group, $viewer), 403);

        $communities->updateGroup($group, $this->validateGroup($request, $communities, true));

        return redirect()->route('front.groups.show', ['community' => $group->id]);
    }

    public function photoAlbums(CommunityRepository $communities, PhotoalbumRepository $photoalbums, ?int $community = null): View
    {
        $group = $this->resolveGroup($community, $communities);
        $payload = $this->groupPayload($group, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $photoalbums->albumsForOwner($group->id, 'group'),
            'photos' => $photoalbums->photosForOwner($group->id, 'group', self::PHOTOS_LIMIT, 0),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreOwnerPhotos($group->id, 'group', self::PHOTOS_LIMIT, 0),
            'popularPhotos' => $photoalbums->popularPhotos(9, 0, 'group'),
        ]);
    }

    public function showPhotoalbum(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $photoalbum = $this->groupPhotoalbumOrFail($album, $group, $photoalbums);
        $payload = $this->groupPayload($group, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoalbum,
            'photos' => $photoalbums->albumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreAlbumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    public function addPhoto(int $community, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoalbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Альбом сообщества');

        return view('front.teams.photoalbums.add-photo', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Добавление фотографий',
            'albums' => $photoalbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    public function createPhotoAlbum(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Создание фотоальбома',
            'action' => route('front.groups.photoalbums.store', ['community' => $group->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    public function storePhotoAlbum(int $community, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($photoalbums->nameExistsForOwner($group->id, 'group', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $photoalbums->createAlbumForOwner($group->id, 'group', $name);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    public function editPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums, ?int $community = null): View
    {
        $photoalbum = $photoalbums->album($album, ['group']);
        abort_if(! $photoalbum, 404);

        $group = $community ? $this->groupOrFail($community, $communities) : $this->groupOrFail((int) $photoalbum->owner_id, $communities);
        $this->groupPhotoalbumOrFail($album, $group, $photoalbums);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Редактирование фотоальбома',
            'action' => route('front.groups.photoalbum.update', ['album' => $photoalbum->id]),
            'name' => old('name', $photoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    public function updatePhotoalbum(int $album, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $photoalbum = $photoalbums->album($album, ['group']);
        abort_if(! $photoalbum, 404);
        $group = $this->groupOrFail((int) $photoalbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoalbums->updateUserAlbum($photoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    public function destroyPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $photoalbum = $photoalbums->album($album, ['group']);
        abort_if(! $photoalbum, 404);

        $group = $this->groupOrFail((int) $photoalbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoalbums->deleteAlbum($photoalbum);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    public function destroyPhotoalbumForGroup(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $photoalbum = $this->groupPhotoalbumOrFail($album, $group, $photoalbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoalbums->deleteAlbum($photoalbum);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    public function editPhotoalbumForGroup(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        return $this->editPhotoalbum($album, $communities, $photoalbums, $community);
    }

    public function updatePhotoalbumForGroup(int $community, int $album, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $photoalbum = $this->groupPhotoalbumOrFail($album, $group, $photoalbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoalbums->updateUserAlbum($photoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    public function photo(int $community, int $album, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $view = $this->showPhotoalbum($community, $album, $communities, $photoalbums);
        $view->with('openPhotoId', $photo);

        return $view;
    }

    public function photoWithoutAlbum(int $community, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $photoModel = $photoalbums->photo($photo, ['group']);
        abort_if(! $photoModel, 404);

        $photoalbum = $this->groupPhotoalbumOrFail((int) $photoModel->photoalbum_id, $group, $photoalbums);

        return $this->photo($community, $photoalbum->id, $photo, $communities, $photoalbums);
    }

    public function videoAlbums(CommunityRepository $communities, VideoalbumRepository $videoalbums, ?int $community = null): View
    {
        $group = $this->resolveGroup($community, $communities);
        $payload = $this->groupPayload($group, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $videoalbums->albumsForOwner($group->id, 'group'),
            'videos' => $videoalbums->videosForOwner($group->id, 'group', self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreOwnerVideos($group->id, 'group', self::VIDEOS_LIMIT, 0),
            'popularVideos' => $videoalbums->popularVideos(6, 0, 'group'),
        ]);
    }

    public function showVideoAlbum(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $videoalbum = $this->groupVideoalbumOrFail($album, $group, $videoalbums);
        $payload = $this->groupPayload($group, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.show', $payload + [
            'videoalbum' => $videoalbum,
            'videos' => $videoalbums->albumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreAlbumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
        ]);
    }

    public function addVideo(int $community, CommunityRepository $communities, VideoalbumRepository $videoalbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoalbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Альбом сообщества');

        return view('front.teams.videoalbums.add-video', $this->groupPayload($group, $communities, 'videoalbums') + [
            'title' => 'Добавление видеозаписи',
            'albums' => $videoalbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    public function storeVideo(int $community, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $validated = $request->validate([
            'video' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'videoalbum_id' => ['required', 'integer', 'min:1'],
        ]);

        $album = $this->groupVideoalbumOrFail((int) $validated['videoalbum_id'], $group, $videoalbums);
        $videoalbums->addVideoToAlbum(Auth::guard('web')->user(), $album, $validated['video'], trim((string) ($validated['description'] ?? '')));

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    public function createVideoAlbum(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'videoalbums') + [
            'title' => 'Создание видеоальбома',
            'action' => route('front.groups.videoalbums.store', ['community' => $group->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    public function storeVideoAlbum(int $community, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($videoalbums->nameExistsForOwner($group->id, 'group', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $videoalbums->createAlbumForOwner($group->id, 'group', $name);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    public function editVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums, ?int $community = null): View
    {
        $videoalbum = $videoalbums->album($album, ['group']);
        abort_if(! $videoalbum, 404);

        $group = $community ? $this->groupOrFail($community, $communities) : $this->groupOrFail((int) $videoalbum->owner_id, $communities);
        $this->groupVideoalbumOrFail($album, $group, $videoalbums);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'videoalbums') + [
            'title' => 'Редактирование видеоальбома',
            'action' => route('front.groups.videoalbum.update', ['album' => $videoalbum->id]),
            'name' => old('name', $videoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    public function updateVideoalbum(int $album, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $videoalbum = $videoalbums->album($album, ['group']);
        abort_if(! $videoalbum, 404);
        $group = $this->groupOrFail((int) $videoalbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoalbums->updateUserAlbum($videoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    public function destroyVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $videoalbum = $videoalbums->album($album, ['group']);
        abort_if(! $videoalbum, 404);

        $group = $this->groupOrFail((int) $videoalbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoalbums->deleteAlbum($videoalbum);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    public function destroyVideoalbumForGroup(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $videoalbum = $this->groupVideoalbumOrFail($album, $group, $videoalbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoalbums->deleteAlbum($videoalbum);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    public function events(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);

        return view('front.teams.events', $this->groupPayload($group, $communities, 'events') + [
            'events' => $communities->events($group->id, 'group'),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
        ]);
    }

    private function groupPayload(Community $group, CommunityRepository $communities, string $section): array
    {
        $viewer = Auth::guard('web')->user();
        $groupData = $communities->serializeGroup($group);

        return [
            'title' => $group->name ?: 'Группа',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'group' => $group,
            'groupData' => $groupData,
            'team' => $group,
            'teamData' => $groupData,
            'permissions' => $communities->permissions($group, $viewer),
            'role' => $communities->role($group->id, $viewer?->id),
            'membershipType' => $communities->membershipType($group, $viewer),
            'canManageGroup' => $communities->canManage($group, $viewer),
            'canManageTeam' => $communities->canManage($group, $viewer),
            'canInviteGroup' => $communities->canInvite($group, $viewer),
            'canInviteTeam' => $communities->canInvite($group, $viewer),
            'section' => $section,
            'communityView' => [
                'kind' => 'group',
                'route' => 'front.groups',
                'top' => 'front.groups._top',
                'label' => 'Группа',
                'labelLower' => 'группа',
                'labelGenitive' => 'группы',
                'pluralGenitive' => 'групп',
                'entity' => $group,
                'data' => $groupData,
            ],
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

    private function groupFilters(Request $request): array
    {
        return [
            'place' => trim((string) $request->query('place', '')),
            'sport' => trim((string) $request->query('sport', '')),
            'search' => trim((string) $request->query('search', '')),
            'id_place' => (int) $request->query('id_place', 0),
            'id_sport' => (int) $request->query('id_sport', 0),
        ];
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

    private function validateAlbumName(Request $request): string
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Укажите название альбома.',
        ]);

        return trim($validated['name']);
    }

    private function resolveGroup(?int $community, CommunityRepository $communities): Community
    {
        if ($community) {
            return $this->groupOrFail($community, $communities);
        }

        $group = $communities->defaultGroup(Auth::guard('web')->user());

        abort_if(! $group, 404);

        return $group;
    }

    private function groupOrFail(int $community, CommunityRepository $communities): Community
    {
        $group = $communities->findGroup($community);

        abort_if(! $group, 404);

        return $group;
    }

    private function groupPhotoalbumOrFail(int $album, Community $group, PhotoalbumRepository $photoalbums): Photoalbum
    {
        $photoalbum = $photoalbums->album($album, ['group']);

        abort_if(! $photoalbum || (int) $photoalbum->owner_id !== (int) $group->id, 404);

        return $photoalbum;
    }

    private function groupVideoalbumOrFail(int $album, Community $group, VideoalbumRepository $videoalbums): Videoalbum
    {
        $videoalbum = $videoalbums->album($album, ['group']);

        abort_if(! $videoalbum || (int) $videoalbum->owner_id !== (int) $group->id, 404);

        return $videoalbum;
    }
}
