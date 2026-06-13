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

class GroupsController extends Controller
{
    private const PAGE_SIZE = 5;
    private const PHOTOS_LIMIT = 6;
    private const ALBUM_PHOTOS_LIMIT = 9;
    private const VIDEOS_LIMIT = 6;
    private const COMMENTS_LIMIT = 10;


    /**
     * Показывает список групп с фильтрами и вкладками текущего пользователя.
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

    /**
     * Показывает групп выбранного пользователя.
     *
     * @param int $user
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Проверяет авторизацию и показывает форму создания группы.
     *
     * @return View|RedirectResponse
     */
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
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Валидирует данные формы и создает группу.
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

        $group = $communities->createGroup($viewer, $request->toDto());

        return redirect()->route('front.groups.show', ['community' => $group->id]);
    }

    /**
     * Показывает карточку группы, верхний блок и комментарии.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param ProfileRepository $profiles
     * @return View
     */
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

    /**
     * Показывает участников группы и их роли.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Проверяет права и показывает форму редактирования группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Проверяет права и сохраняет изменения группы
     *
     * @param int $community
     * @param CommunityRequest $request
     * @param CommunityRepository $communities
     * @return RedirectResponse
     */
    public function update(int $community, CommunityRequest $request, CommunityRepository $communities): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($communities->canManage($group, $viewer), 403);

        $communities->updateGroup($group, $request->toDto(true));

        return redirect()->route('front.groups.show', ['community' => $group->id]);
    }

    /**
     * Показывает фотоальбомы группы или текущей группы.
     *
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @param int|null $community
     * @return View
     */
    public function photoAlbums(CommunityRepository $communities, PhotoalbumRepository $photoAlbums, ?int $community = null): View
    {
        $group = $this->resolveGroup($community, $communities);
        $payload = $this->groupPayload($group, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $photoAlbums->albumsForOwner($group->id, 'group'),
            'photos' => $photoAlbums->photosForOwner($group->id, 'group', self::PHOTOS_LIMIT, 0),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $photoAlbums->hasMoreOwnerPhotos($group->id, 'group', self::PHOTOS_LIMIT, 0),
            'popularPhotos' => $photoAlbums->popularPhotos(9, 0, 'group'),
        ]);
    }

    /**
     * Показывает фотографии выбранного фотоальбома группы.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function showPhotoalbum(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $photoAlbum = $this->groupPhotoalbumOrFail($album, $group, $photoAlbums);
        $payload = $this->groupPayload($group, $communities, 'photoalbums');
        abort_unless($payload['permissions']['photo'], 404);

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoAlbum,
            'photos' => $photoAlbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $photoAlbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    /**
     * Показывает форму добавления фотографии в фотоальбом группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function addPhoto(int $community, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoAlbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Альбом сообщества');

        return view('front.teams.photoalbums.add-photo', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Добавление фотографий',
            'albums' => $photoAlbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    /**
     * Показывает форму создания фотоальбома группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Создает фотоальбом группы из валидированных данных формы.
     *
     * @param int $community
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function storePhotoAlbum(int $community, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $albumData = $request->toDto();

        if ($photoAlbums->nameExistsForOwner($group->id, 'group', $albumData->name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $photoAlbums->createAlbumForOwner($group->id, 'group', $albumData);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Проверяет права и показывает форму редактирования фотоальбома группы.
     */
    public function editPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums, ?int $community = null): View
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);
        abort_if(! $photoAlbum, 404);

        $group = $community ? $this->groupOrFail($community, $communities) : $this->groupOrFail((int) $photoAlbum->owner_id, $communities);
        $this->groupPhotoalbumOrFail($album, $group, $photoAlbums);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Редактирование фотоальбома',
            'action' => route('front.groups.photoalbum.update', ['album' => $photoAlbum->id]),
            'name' => old('name', $photoAlbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения фотоальбома группы.
     */
    public function updatePhotoalbum(int $album, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);
        abort_if(! $photoAlbum, 404);
        $group = $this->groupOrFail((int) $photoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoAlbums->updateUserAlbum($photoAlbum, $request->toDto());

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Проверяет права и удаляет фотоальбом группы.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function destroyPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);
        abort_if(! $photoAlbum, 404);

        $group = $this->groupOrFail((int) $photoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoAlbums->deleteAlbum($photoAlbum);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Проверяет группу в URL и удаляет ее фотоальбом.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function destroyPhotoalbumForGroup(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $photoAlbum = $this->groupPhotoalbumOrFail($album, $group, $photoAlbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoAlbums->deleteAlbum($photoAlbum);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Показывает форму редактирования фотоальбома конкретной группы.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function editPhotoalbumForGroup(int $community, int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        return $this->editPhotoalbum($album, $communities, $photoAlbums, $community);
    }

    /**
     * Сохраняет изменения фотоальбома конкретной группы.
     */
    public function updatePhotoalbumForGroup(int $community, int $album, AlbumRequest $request, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $photoAlbum = $this->groupPhotoalbumOrFail($album, $group, $photoAlbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $photoAlbums->updateUserAlbum($photoAlbum, $request->toDto());

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Показывает конкретную фотографию из фотоальбома группы.
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
     * Показывает фотографию группы без привязки к выбранному альбому.
     *
     * @param int $community
     * @param int $photo
     * @param CommunityRepository $communities
     * @param PhotoalbumRepository $photoAlbums
     * @return View
     */
    public function photoWithoutAlbum(int $community, int $photo, CommunityRepository $communities, PhotoalbumRepository $photoAlbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $photoModel = $photoAlbums->photo($photo, ['group']);
        abort_if(! $photoModel, 404);

        $photoAlbum = $this->groupPhotoalbumOrFail((int) $photoModel->photoalbum_id, $group, $photoAlbums);

        return $this->photo($community, $photoAlbum->id, $photo, $communities, $photoAlbums);
    }

    /**
     * Показывает видеоальбомы группы или текущей группы.
     *
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @param int|null $community
     * @return View
     */
    public function videoAlbums(CommunityRepository $communities, VideoalbumRepository $videoAlbums, ?int $community = null): View
    {
        $group = $this->resolveGroup($community, $communities);
        $payload = $this->groupPayload($group, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $videoAlbums->albumsForOwner($group->id, 'group'),
            'videos' => $videoAlbums->videosForOwner($group->id, 'group', self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoAlbums->hasMoreOwnerVideos($group->id, 'group', self::VIDEOS_LIMIT, 0),
            'popularVideos' => $videoAlbums->popularVideos(6, 0, 'group'),
        ]);
    }

    /**
     * Показывает видео выбранного видеоальбома групп
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return View
     */
    public function showVideoAlbum(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        $videoAlbum = $this->groupVideoalbumOrFail($album, $group, $videoAlbums);
        $payload = $this->groupPayload($group, $communities, 'videoalbums');
        abort_unless($payload['permissions']['video'], 404);

        return view('front.teams.videoalbums.show', $payload + [
            'videoAlbum' => $videoAlbum,
            'videos' => $videoAlbums->albumVideos($videoAlbum, self::VIDEOS_LIMIT, 0),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $videoAlbums->hasMoreAlbumVideos($videoAlbum, self::VIDEOS_LIMIT, 0),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Показывает форму добавления видео в видеоальбом группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return View
     */
    public function addVideo(int $community, CommunityRepository $communities, VideoalbumRepository $videoAlbums): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoAlbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Альбом сообщества');

        return view('front.teams.videoalbums.add-video', $this->groupPayload($group, $communities, 'videoalbums') + [
            'title' => 'Добавление видеозаписи',
            'albums' => $videoAlbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    /**
     * Валидирует ссылку и добавляет видео в видеоальбом группы.
     *
     * @param int $community
     * @param StoreVideoRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function storeVideo(int $community, StoreVideoRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoData = $request->toDto();
        $album = $this->groupVideoalbumOrFail($videoData->albumId, $group, $videoAlbums);
        $videoAlbums->addVideoToAlbum(Auth::guard('web')->user(), $album, $videoData);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    /**
     * Показывает форму создания видеоальбома группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
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

    /**
     * Создает видеоальбом группы из валидированных данных формы
     *
     * @param int $community
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function storeVideoAlbum(int $community, AlbumRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $albumData = $request->toDto();

        if ($videoAlbums->nameExistsForOwner($group->id, 'group', $albumData->name)) {
            return back()->withErrors(['name' => 'Альбом с таким названием уже существует.'])->withInput();
        }

        $videoAlbums->createAlbumForOwner($group->id, 'group', $albumData);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    /**
     * Проверяет права и показывает форму редактирования видеоальбома группы.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @param int|null $community
     * @return View
     */
    public function editVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums, ?int $community = null): View
    {
        $videoAlbum = $videoAlbums->album($album, ['group']);
        abort_if(! $videoAlbum, 404);

        $group = $community ? $this->groupOrFail($community, $communities) : $this->groupOrFail((int) $videoAlbum->owner_id, $communities);
        $this->groupVideoalbumOrFail($album, $group, $videoAlbums);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'videoalbums') + [
            'title' => 'Редактирование видеоальбома',
            'action' => route('front.groups.videoalbum.update', ['album' => $videoAlbum->id]),
            'name' => old('name', $videoAlbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения видеоальбома группы.
     *
     * @param int $album
     * @param AlbumRequest $request
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function updateVideoalbum(int $album, AlbumRequest $request, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $videoAlbum = $videoAlbums->album($album, ['group']);
        abort_if(! $videoAlbum, 404);
        $group = $this->groupOrFail((int) $videoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoAlbums->updateUserAlbum($videoAlbum, $request->toDto());

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    /**
     * Проверяет права и удаляет видеоальбом группы.
     *
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function destroyVideoalbum(int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $videoAlbum = $videoAlbums->album($album, ['group']);
        abort_if(! $videoAlbum, 404);

        $group = $this->groupOrFail((int) $videoAlbum->owner_id, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoAlbums->deleteAlbum($videoAlbum);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }


    /**
     * Проверяет группу в URL и удаляет ее видеоальбом.
     *
     * @param int $community
     * @param int $album
     * @param CommunityRepository $communities
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function destroyVideoalbumForGroup(int $community, int $album, CommunityRepository $communities, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $videoAlbum = $this->groupVideoalbumOrFail($album, $group, $videoAlbums);

        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        $videoAlbums->deleteAlbum($videoAlbum);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    /**
     * Показывает мероприятия группы.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function events(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);

        return view('front.teams.events', $this->groupPayload($group, $communities, 'events') + [
            'events' => $communities->events($group->id, 'group'),
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Готовит общие данные группы для страниц вложенных разделов.
     */
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

    /**
     * Добавляет к списку групп данные о правах и статусе текущего пользователя.
     */
    private function groupsForViewer(Collection $groups, CommunityRepository $communities, ?User $viewer): Collection
    {
        return $groups->map(function (array $group) use ($communities, $viewer): array {
            $role = $communities->role((int) $group['id'], $viewer?->id);

            $group['status'] = $communities->roleLabel($role);
            $group['can_edit'] = $role === 1;

            return $group;
        });
    }

    /**
     * Собирает фильтры списка групп из query-параметров.
     */
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

    /**
     * Определяет группу из параметра маршрута или из текущего пользователя.
     */
    private function resolveGroup(?int $community, CommunityRepository $communities): Community
    {
        if ($community) {
            return $this->groupOrFail($community, $communities);
        }

        $group = $communities->defaultGroup(Auth::guard('web')->user());

        abort_if(! $group, 404);

        return $group;
    }

    /**
     * Находит активную группу или завершает запрос ошибкой 404.
     */
    private function groupOrFail(int $community, CommunityRepository $communities): Community
    {
        $group = $communities->findGroup($community);

        abort_if(! $group, 404);

        return $group;
    }

    /**
     * Находит фотоальбом, принадлежащий группе, или завершает запрос ошибкой 404.
     */
    private function groupPhotoalbumOrFail(int $album, Community $group, PhotoalbumRepository $photoAlbums): PhotoAlbums
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);

        abort_if(! $photoAlbum || (int) $photoAlbum->owner_id !== (int) $group->id, 404);

        return $photoAlbum;
    }

    /**
     * Находит видеоальбом, принадлежащий группе, или завершает запрос ошибкой 404.
     */
    private function groupVideoalbumOrFail(int $album, Community $group, VideoalbumRepository $videoAlbums): VideoAlbums
    {
        $videoAlbum = $videoAlbums->album($album, ['group']);

        abort_if(! $videoAlbum || (int) $videoAlbum->owner_id !== (int) $group->id, 404);

        return $videoAlbum;
    }
}
