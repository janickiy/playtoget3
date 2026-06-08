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

class TeamsController extends Controller
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

        $filters = $this->teamFilters($request);

        return view('front.teams.index', [
            'title' => 'Команды',
            'myTeams' => $this->teamsForViewer($communities->myTeams($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'popularTeams' => $this->teamsForViewer($communities->popularTeams(self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'invitedTeams' => $this->teamsForViewer($communities->invitedTeams($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'myTeamsTotal' => $communities->myTeamsCount($viewer->id, $filters),
            'popularTeamsTotal' => $communities->popularTeamsCount($filters),
            'invitedTeamsTotal' => $communities->invitedTeamsCount($viewer->id, $filters),
            'teamsPageSize' => self::PAGE_SIZE,
            'viewer' => $viewer,
        ]);
    }

    public function user(int $user, CommunityRepository $communities): View
    {
        return view('front.teams.index', [
            'title' => 'Команды пользователя',
            'myTeams' => $this->teamsForViewer($communities->myTeams($user, 20), $communities, Auth::guard('web')->user()),
            'popularTeams' => collect(),
            'invitedTeams' => collect(),
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

        return view('front.teams.form', [
            'title' => 'Создание команды',
            'action' => route('front.teams.store'),
            'button' => 'Создать команду',
            'team' => null,
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

        $validated = $this->validateTeam($request, $communities);
        $team = $communities->createTeam($viewer, $validated);

        return redirect()->route('front.teams.show', ['community' => $team->id]);
    }

    public function show(int $community, CommunityRepository $communities, ProfileRepository $profiles): View
    {
        $team = $this->teamOrFail($community, $communities);
        $payload = $this->teamPayload($team, $communities, 'feed');

        return view('front.teams.feed', $payload + [
            'comments' => $payload['permissions']['wall']
                ? $profiles->comments('team', $team->id, self::COMMENTS_LIMIT, 0, Auth::guard('web')->user())
                : collect(),
            'commentsPageSize' => self::COMMENTS_LIMIT,
            'hasMoreComments' => $payload['permissions']['wall']
                ? $profiles->hasMoreComments('team', $team->id, self::COMMENTS_LIMIT, 0)
                : false,
        ]);
    }

    public function members(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);

        return view('front.teams.members', $this->teamPayload($team, $communities, 'members') + [
            'members' => $communities->members($team->id),
            'applications' => $communities->canManage($team, Auth::guard('web')->user())
                ? $communities->applications($team->id)
                : collect(),
        ]);
    }

    public function edit(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($team, $viewer), 403);

        return view('front.teams.form', array_merge($this->teamPayload($team, $communities, 'edit'), [
            'title' => 'Редактирование команды',
            'action' => route('front.teams.update', ['community' => $team->id]),
            'button' => 'Сохранить',
            'team' => $team,
            'settings' => $communities->settings($team),
            'canEditSettings' => true,
            'admins' => $communities->admins($team->id),
            'blocked' => $communities->blocked($team->id),
        ]));
    }

    public function update(int $community, Request $request, CommunityRepository $communities): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($team, $viewer), 403);

        $communities->updateTeam($team, $this->validateTeam($request, $communities, true));

        return redirect()->route('front.teams.show', ['community' => $team->id]);
    }

    public function photoalbums(CommunityRepository $communities, PhotoalbumRepository $photoalbums, ?int $community = null): View
    {
        $team = $this->resolveTeam($community, $communities);
        $payload = $this->teamPayload($team, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $photoalbums->albumsForOwner($team->id, 'team'),
            'photos' => $photoalbums->photosForOwner($team->id, 'team', self::PHOTOS_LIMIT, 0),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreOwnerPhotos($team->id, 'team', self::PHOTOS_LIMIT, 0),
            'popularPhotos' => $photoalbums->popularPhotos(9, 0, 'team'),
        ]);
    }

    public function showPhotoalbum(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $photoalbum = $this->teamPhotoalbumOrFail($album, $team, $photoalbums);
        $payload = $this->teamPayload($team, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoalbum,
            'photos' => $photoalbums->albumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $photoalbums->hasMoreAlbumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    public function addPhoto(int $community, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoalbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Альбом сообщества');

        return view('front.teams.photoalbums.add-photo', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Добавление фотографий',
            'albums' => $photoalbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    public function createPhotoalbum(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Создание фотоальбома',
            'action' => route('front.teams.photoalbums.store', ['community' => $team->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    public function storePhotoalbum(int $community, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($photoalbums->nameExistsForOwner($team->id, 'team', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $photoalbums->createAlbumForOwner($team->id, 'team', $name);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    public function editPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums, ?int $community = null): View
    {
        $photoalbum = $photoalbums->album($album, ['team']);
        abort_if(! $photoalbum, 404);

        $team = $community ? $this->teamOrFail($community, $communities) : $this->teamOrFail((int) $photoalbum->owner_id, $communities);
        $this->teamPhotoalbumOrFail($album, $team, $photoalbums);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Редактирование фотоальбома',
            'action' => route('front.teams.photoalbum.update', ['album' => $photoalbum->id]),
            'name' => old('name', $photoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    public function updatePhotoalbum(int $album, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $photoalbum = $photoalbums->album($album, ['team']);
        abort_if(! $photoalbum, 404);
        $team = $this->teamOrFail((int) $photoalbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);
        $photoalbums->updateUserAlbum($photoalbum, $name);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    public function destroyPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $photoalbum = $photoalbums->album($album, ['team']);
        abort_if(! $photoalbum, 404);

        $team = $this->teamOrFail((int) $photoalbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoalbums->deleteAlbum($photoalbum);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    public function editPhotoalbumForTeam(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        return $this->editPhotoalbum($album, $communities, $photoalbums, $community);
    }

    public function updatePhotoalbumForTeam(int $community, int $album, Request $request, CommunityRepository $communities, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        $photoalbum = $this->teamPhotoalbumOrFail($album, $team, $photoalbums);

        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoalbums->updateUserAlbum($photoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    public function photo(int $community, int $album, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $view = $this->showPhotoalbum($community, $album, $communities, $photoalbums);
        $view->with('openPhotoId', $photo);

        return $view;
    }

    public function photoWithoutAlbum(int $community, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoalbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $photoModel = $photoalbums->photo($photo, ['team']);
        abort_if(! $photoModel, 404);

        $photoalbum = $this->teamPhotoalbumOrFail((int) $photoModel->photoalbum_id, $team, $photoalbums);

        return $this->photo($community, $photoalbum->id, $photo, $communities, $photoalbums);
    }

    public function videoalbums(CommunityRepository $communities, VideoalbumRepository $videoalbums, ?int $community = null): View
    {
        $team = $this->resolveTeam($community, $communities);
        $payload = $this->teamPayload($team, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $videoalbums->albumsForOwner($team->id, 'team'),
            'videos' => $videoalbums->videosForOwner($team->id, 'team', self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreOwnerVideos($team->id, 'team', self::VIDEOS_LIMIT, 0),
            'popularVideos' => $videoalbums->popularVideos(6, 0, 'team'),
        ]);
    }

    public function showVideoalbum(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $videoalbum = $this->teamVideoalbumOrFail($album, $team, $videoalbums);
        $payload = $this->teamPayload($team, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.show', $payload + [
            'videoalbum' => $videoalbum,
            'videos' => $videoalbums->albumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoalbums->hasMoreAlbumVideos($videoalbum, self::VIDEOS_LIMIT, 0),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
        ]);
    }

    public function addVideo(int $community, CommunityRepository $communities, VideoalbumRepository $videoalbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoalbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Альбом сообщества');

        return view('front.teams.videoalbums.add-video', $this->teamPayload($team, $communities, 'videoalbums') + [
            'title' => 'Добавление видеозаписи',
            'albums' => $videoalbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    public function storeVideo(int $community, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $validated = $request->validate([
            'video' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'videoalbum_id' => ['required', 'integer', 'min:1'],
        ]);

        $album = $this->teamVideoalbumOrFail((int) $validated['videoalbum_id'], $team, $videoalbums);
        $videoalbums->addVideoToAlbum(Auth::guard('web')->user(), $album, $validated['video'], trim((string) ($validated['description'] ?? '')));

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    public function createVideoalbum(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'videoalbums') + [
            'title' => 'Создание видеоальбома',
            'action' => route('front.teams.videoalbums.store', ['community' => $team->id]),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    public function storeVideoalbum(int $community, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $name = $this->validateAlbumName($request);

        if ($videoalbums->nameExistsForOwner($team->id, 'team', $name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $videoalbums->createAlbumForOwner($team->id, 'team', $name);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    public function editVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums, ?int $community = null): View
    {
        $videoalbum = $videoalbums->album($album, ['team']);
        abort_if(! $videoalbum, 404);

        $team = $community ? $this->teamOrFail($community, $communities) : $this->teamOrFail((int) $videoalbum->owner_id, $communities);
        $this->teamVideoalbumOrFail($album, $team, $videoalbums);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'videoalbums') + [
            'title' => 'Редактирование видеоальбома',
            'action' => route('front.teams.videoalbum.update', ['album' => $videoalbum->id]),
            'name' => old('name', $videoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    public function updateVideoalbum(int $album, Request $request, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $videoalbum = $videoalbums->album($album, ['team']);
        abort_if(! $videoalbum, 404);
        $team = $this->teamOrFail((int) $videoalbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoalbums->updateUserAlbum($videoalbum, $this->validateAlbumName($request));

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    public function destroyVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $videoalbum = $videoalbums->album($album, ['team']);
        abort_if(! $videoalbum, 404);

        $team = $this->teamOrFail((int) $videoalbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoalbums->deleteAlbum($videoalbum);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    public function events(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);

        return view('front.teams.events', $this->teamPayload($team, $communities, 'events') + [
            'events' => $communities->events($team->id),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
        ]);
    }

    private function teamPayload(Community $team, CommunityRepository $communities, string $section): array
    {
        $viewer = Auth::guard('web')->user();

        return [
            'title' => $team->name ?: 'Команда',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'team' => $team,
            'teamData' => $communities->serializeTeam($team),
            'permissions' => $communities->permissions($team, $viewer),
            'role' => $communities->role($team->id, $viewer?->id),
            'membershipType' => $communities->membershipType($team, $viewer),
            'canManageTeam' => $communities->canManage($team, $viewer),
            'canInviteTeam' => $communities->canInvite($team, $viewer),
            'section' => $section,
        ];
    }

    private function teamsForViewer(Collection $teams, CommunityRepository $communities, ?User $viewer): Collection
    {
        return $teams->map(function (array $team) use ($communities, $viewer): array {
            $role = $communities->role((int) $team['id'], $viewer?->id);

            $team['status'] = $communities->roleLabel($role);
            $team['can_edit'] = $role === 1;

            return $team;
        });
    }

    private function teamFilters(Request $request): array
    {
        return [
            'place' => trim((string) $request->query('place', '')),
            'sport' => trim((string) $request->query('sport', '')),
            'search' => trim((string) $request->query('search', '')),
            'id_place' => (int) $request->query('id_place', 0),
            'id_sport' => (int) $request->query('id_sport', 0),
        ];
    }

    private function validateTeam(Request $request, CommunityRepository $communities, bool $withSettings = false): array
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
            'name.required' => 'Укажите название команды.',
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

    private function resolveTeam(?int $community, CommunityRepository $communities): Community
    {
        if ($community) {
            return $this->teamOrFail($community, $communities);
        }

        $team = $communities->defaultTeam(Auth::guard('web')->user());

        abort_if(! $team, 404);

        return $team;
    }

    private function teamOrFail(int $community, CommunityRepository $communities): Community
    {
        $team = $communities->findTeam($community);

        abort_if(! $team, 404);

        return $team;
    }

    private function teamPhotoalbumOrFail(int $album, Community $team, PhotoalbumRepository $photoalbums): Photoalbum
    {
        $photoalbum = $photoalbums->album($album, ['team']);

        abort_if(! $photoalbum || (int) $photoalbum->owner_id !== (int) $team->id, 404);

        return $photoalbum;
    }

    private function teamVideoalbumOrFail(int $album, Community $team, VideoalbumRepository $videoalbums): Videoalbum
    {
        $videoalbum = $videoalbums->album($album, ['team']);

        abort_if(! $videoalbum || (int) $videoalbum->owner_id !== (int) $team->id, 404);

        return $videoalbum;
    }
}
