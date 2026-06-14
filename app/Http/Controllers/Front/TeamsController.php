<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Album\AlbumRequest;
use App\Http\Requests\Front\Community\CommunityRequest;
use App\Http\Requests\Front\Video\StoreVideoRequest;
use App\Models\Community;
use App\Models\PhotoAlbums;
use App\Models\User;
use App\Models\VideoAlbums;
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


    /**
     * Показывает список команд с фильтрами и вкладками текущего пользователя.
     *
     * @param Request $request
     * @param CommunityRepository $communities
     * @return View|RedirectResponse
     */
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

    /**
     * Показывает команд выбранного пользователя.
     *
     * @param int $user
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Проверяет авторизацию и показывает форму создания команды.
     *
     * @return View|RedirectResponse
     */
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
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Валидирует данные формы и создает команду.
     *
     * @param CommunityRequest $request
     * @param CommunityRepository $communities
     * @return RedirectResponse
     */
    public function store(CommunityRequest $request, CommunityRepository $communities): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (! $viewer) {
            return redirect()->route('front.home');
        }

        $team = $communities->createTeam($viewer, $request->toDto());

        return redirect()->route('front.teams.show', ['community' => $team->id]);
    }

    /**
     * Показывает карточку команды, верхний блок и комментарии.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param ProfileRepository $profiles
     * @return View
     */
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

    /**
     * Показывает участников команды и их роли.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function members(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        return view('front.teams.members', $this->teamPayload($team, $communities, 'members') + [
            'members' => $communities->members($team->id, $viewer?->id),
            'applications' => $communities->canManage($team, $viewer)
                ? $communities->applications($team->id)
                : collect(),
        ]);
    }

    /**
     * Проверяет права и показывает форму редактирования команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Проверяет права и сохраняет изменения команды.
     *
     * @param int $community
     * @param CommunityRequest $request
     * @param CommunityRepository $communities
     * @return RedirectResponse
     */
    public function update(int $community, CommunityRequest $request, CommunityRepository $communities): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($team, $viewer), 403);

        $communities->updateTeam($team, $request->toDto(true));

        return redirect()->route('front.teams.show', ['community' => $team->id]);
    }

    /**
     * Показывает фотоальбомы команды или текущей команды.
     *
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @param int|null $community
     * @return View
     */
    public function photoAlbums(CommunityRepository $communities, PhotoalbumRepository $photoAlbums, ?int $community = null): View
    {
        $team = $this->resolveTeam($community, $communities);
        $payload = $this->teamPayload($team, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $photoAlbums->albumsForOwner($team->id, 'team'),
            'photos' => $photoAlbums->photosForOwner($team->id, 'team', self::PHOTOS_LIMIT, 0),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $photoAlbums->hasMoreOwnerPhotos($team->id, 'team', self::PHOTOS_LIMIT, 0),
            'popularPhotos' => $photoAlbums->popularPhotos(9, 0, 'team'),
        ]);
    }

    /**
     * Показывает фотографии выбранного фотоальбома команды.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function showPhotoalbum(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $photoAlbum = $this->teamPhotoalbumOrFail($album, $team, $photoAlbums);
        $payload = $this->teamPayload($team, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoAlbum,
            'photos' => $photoAlbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $photoAlbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    /**
     * Показывает форму добавления фотографии в фотоальбом команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function addPhoto(int $community, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoAlbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Альбом сообщества');

        return view('front.teams.photoalbums.add-photo', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Добавление фотографий',
            'albums' => $photoAlbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    /**
     * Показывает форму создания фотоальбома команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createPhotoAlbum(int $community, CommunityRepository $communities): View
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

    /**
     * Создает фотоальбом команды из валидированных данных формы.
     *
     * @param int $community
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function storePhotoAlbum(int $community, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $albumData = $request->toDto();

        if ($photoAlbums->nameExistsForOwner($team->id, 'team', $albumData->name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $photoAlbums->createAlbumForOwner($team->id, 'team', $albumData);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    /**
     * Проверяет права и показывает форму редактирования фотоальбома команды.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @param int|null $community
     * @return View
     */
    public function editPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums, ?int $community = null): View
    {
        $photoAlbum = $photoAlbums->album($album, ['team']);
        abort_if(! $photoAlbum, 404);

        $team = $community ? $this->teamOrFail($community, $communities) : $this->teamOrFail((int) $photoAlbum->owner_id, $communities);
        $this->teamPhotoalbumOrFail($album, $team, $photoAlbums);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Редактирование фотоальбома',
            'action' => route('front.teams.photoalbum.update', ['album' => $photoAlbum->id]),
            'name' => old('name', $photoAlbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения фотоальбома команды.
     *
     * @param int $album
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function updatePhotoalbum(int $album, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $photoAlbum = $photoAlbums->album($album, ['team']);
        abort_if(! $photoAlbum, 404);
        $team = $this->teamOrFail((int) $photoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoAlbums->updateUserAlbum($photoAlbum, $request->toDto());

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    /**
     * Проверяет права и удаляет фотоальбом команды.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function destroyPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $photoAlbum = $photoAlbums->album($album, ['team']);
        abort_if(! $photoAlbum, 404);

        $team = $this->teamOrFail((int) $photoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoAlbums->deleteAlbum($photoAlbum);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    /**
     * Показывает форму редактирования фотоальбома конкретной команды
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function editPhotoalbumForTeam(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        return $this->editPhotoalbum($album, $communities, $photoAlbums, $community);
    }

    /**
     * Сохраняет изменения фотоальбома конкретной команды.
     *
     * @param int $community
     * @param int $album
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function updatePhotoalbumForTeam(int $community, int $album, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        $photoAlbum = $this->teamPhotoalbumOrFail($album, $team, $photoAlbums);

        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $photoAlbums->updateUserAlbum($photoAlbum, $request->toDto());

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    /**
     * Показывает конкретную фотографию из фотоальбома команды.
     *
     * @param int $community
     * @param int $album
     * @param int $photo
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function photo(int $community, int $album, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $view = $this->showPhotoalbum($community, $album, $communities, $photoAlbums);
        $view->with('openPhotoId', $photo);

        return $view;
    }

    /**
     * Показывает фотографию команды без привязки к выбранному альбому.
     *
     * @param int $community
     * @param int $photo
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function photoWithoutAlbum(int $community, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $photoModel = $photoAlbums->photo($photo, ['team']);
        abort_if(! $photoModel, 404);

        $photoAlbum = $this->teamPhotoalbumOrFail((int) $photoModel->photoalbum_id, $team, $photoAlbums);

        return $this->photo($community, $photoAlbum->id, $photo, $communities, $photoAlbums);
    }

    /**
     * Показывает видеоальбомы команды или текущей команды.
     *
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @param int|null $community
     * @return View
     */
    public function videoAlbums(CommunityRepository $communities, VideoalbumRepository $videoAlbums, ?int $community = null): View
    {
        $team = $this->resolveTeam($community, $communities);
        $payload = $this->teamPayload($team, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $videoAlbums->albumsForOwner($team->id, 'team'),
            'videos' => $videoAlbums->videosForOwner($team->id, 'team', self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoAlbums->hasMoreOwnerVideos($team->id, 'team', self::VIDEOS_LIMIT, 0),
            'popularVideos' => $videoAlbums->popularVideos(6, 0, 'team'),
        ]);
    }

    /**
     * Показывает видео выбранного видеоальбома команды.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return View
     */
    public function showVideoAlbum(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        $videoAlbum = $this->teamVideoalbumOrFail($album, $team, $videoAlbums);
        $payload = $this->teamPayload($team, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.show', $payload + [
            'videoAlbum' => $videoAlbum,
            'videos' => $videoAlbums->albumVideos($videoAlbum, self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoAlbums->hasMoreAlbumVideos($videoAlbum, self::VIDEOS_LIMIT, 0),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Показывает форму добавления видео в видеоальбом команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return View
     */
    public function addVideo(int $community, CommunityRepository $communities, VideoalbumRepository $videoAlbums): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoAlbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Альбом сообщества');

        return view('front.teams.videoalbums.add-video', $this->teamPayload($team, $communities, 'videoalbums') + [
            'title' => 'Добавление видеозаписи',
            'albums' => $videoAlbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    /**
     * Валидирует ссылку и добавляет видео в видеоальбом команды.
     *
     * @param int $community
     * @param StoreVideoRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function storeVideo(int $community, StoreVideoRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoData = $request->toDto();
        $album = $this->teamVideoalbumOrFail($videoData->albumId, $team, $videoAlbums);
        $videoAlbums->addVideoToAlbum(Auth::guard('web')->user(), $album, $videoData);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    /**
     * Показывает форму создания видеоальбома команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createVideoAlbum(int $community, CommunityRepository $communities): View
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

    /**
     * Создает видеоальбом команды из валидированных данных формы.
     *
     * @param int $community
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function storeVideoAlbum(int $community, AlbumRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $albumData = $request->toDto();

        if ($videoAlbums->nameExistsForOwner($team->id, 'team', $albumData->name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $videoAlbums->createAlbumForOwner($team->id, 'team', $albumData);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    /**
     * Проверяет права и показывает форму редактирования видеоальбома команды.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @param int|null $community
     * @return View
     */
    public function editVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums, ?int $community = null): View
    {
        $videoAlbum = $videoAlbums->album($album, ['team']);
        abort_if(! $videoAlbum, 404);

        $team = $community ? $this->teamOrFail($community, $communities) : $this->teamOrFail((int) $videoAlbum->owner_id, $communities);
        $this->teamVideoalbumOrFail($album, $team, $videoAlbums);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->teamPayload($team, $communities, 'videoalbums') + [
            'title' => 'Редактирование видеоальбома',
            'action' => route('front.teams.videoalbum.update', ['album' => $videoAlbum->id]),
            'name' => old('name', $videoAlbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения видеоальбома команды
     *
     * @param int $album
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function updateVideoalbum(int $album, AlbumRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $videoAlbum = $videoAlbums->album($album, ['team']);
        abort_if(! $videoAlbum, 404);
        $team = $this->teamOrFail((int) $videoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoAlbums->updateUserAlbum($videoAlbum, $request->toDto());

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    /**
     * Проверяет права и удаляет видеоальбом команды.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function destroyVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $videoAlbum = $videoAlbums->album($album, ['team']);
        abort_if(! $videoAlbum, 404);

        $team = $this->teamOrFail((int) $videoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        $videoAlbums->deleteAlbum($videoAlbum);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    /**
     * Показывает мероприятия команды.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function events(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);

        return view('front.teams.events', $this->teamPayload($team, $communities, 'events') + [
            'events' => $communities->events($team->id),
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Готовит общие данные команды для страниц вложенных разделов.
     *
     * @param Community $team
     * @param CommunityRepository $communities
     * @param string $section
     * @return array
     */
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

    /**
     * Добавляет к списку команд данные о правах и статусе текущего пользователя.
     *
     * @param Collection $teams
     * @param CommunityRepository $communities
     * @param User|null $viewer
     * @return Collection
     */
    private function teamsForViewer(Collection $teams, CommunityRepository $communities, ?User $viewer): Collection
    {
        return $teams->map(function (array $team) use ($communities, $viewer): array {
            $role = $communities->role((int) $team['id'], $viewer?->id);

            $team['status'] = $communities->roleLabel($role);
            $team['can_edit'] = $role === 1;

            return $team;
        });
    }

    /**
     * Собирает фильтры списка команд из query-параметров.
     *
     * @param Request $request
     * @return array
     */
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

    /**
     * Определяет команду из параметра маршрута или из текущего пользователя.
     */
    private function resolveTeam(?int $community, CommunityRepository $communities): Community
    {
        if ($community) {
            return $this->teamOrFail($community, $communities);
        }

        $team = $communities->defaultTeam(Auth::guard('web')->user());

        abort_if(! $team, 404);

        return $team;
    }

    /**
     * Находит активную команду или завершает запрос ошибкой 404.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return Community
     */
    private function teamOrFail(int $community, CommunityRepository $communities): Community
    {
        $team = $communities->findTeam($community);

        abort_if(! $team, 404);

        return $team;
    }

    /**
     * Находит фотоальбом, принадлежащий команде, или завершает запрос ошибкой 404
     *
     * @param int $album
     * @param Community $team
     * @param PhotoalbumRepository $photoAlbums
     * @return PhotoAlbums
     */
    private function teamPhotoalbumOrFail(int $album, Community $team, PhotoalbumRepository $photoAlbums): PhotoAlbums
    {
        $photoAlbum = $photoAlbums->album($album, ['team']);

        abort_if(! $photoAlbum || (int) $photoAlbum->owner_id !== (int) $team->id, 404);

        return $photoAlbum;
    }

    /**
     * Находит видеоальбом, принадлежащий команде, или завершает запрос ошибкой 404
     *
     * @param int $album
     * @param Community $team
     * @param VideoalbumRepository $videoAlbums
     * @return VideoAlbums
     */
    private function teamVideoalbumOrFail(int $album, Community $team, VideoalbumRepository $videoAlbums): VideoAlbums
    {
        $videoAlbum = $videoAlbums->album($album, ['team']);

        abort_if(! $videoAlbum || (int) $videoAlbum->owner_id !== (int) $team->id, 404);

        return $videoAlbum;
    }
}
