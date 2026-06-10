<?php

namespace App\Http\Controllers\Front;

use App\Helpers\FrontAssets;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Album\AlbumRequest;
use App\Http\Requests\Front\Video\StoreVideoRequest;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VideoalbumsController extends Controller
{
    private const USER_VIDEOS_LIMIT = 6;
    private const ALBUM_VIDEOS_LIMIT = 6;

    /**
     * Показывает видеоальбомы текущего пользователя.
     */
    public function index(
        VideoalbumRepository $videoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return $this->listing($viewer->id, $videoAlbums, $profiles, $friends, true);
    }

    /**
     * Показывает видеоальбомы выбранного пользователя.
     */
    public function user(
        int                  $user,
        VideoalbumRepository $videoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        return $this->listing($user, $videoAlbums, $profiles, $friends, false);
    }

    /**
     * Показывает видео выбранного видеоальбома.
     *
     * @param int $album
     * @param VideoalbumRepository $videoAlbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function show(
        int                  $album,
        VideoalbumRepository $videoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        $videoalbum = $videoAlbums->album($album);

        abort_if(!$videoalbum || !$videoalbum->owner, 404);

        $profile = $profiles->profile($videoalbum->owner_id);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $videos = $permissions['video']
            ? $videoAlbums->albumVideos($videoalbum, self::ALBUM_VIDEOS_LIMIT, 0)
            : collect();

        return view('front.videoalbums.show', [
            'title' => 'Видеоальбомы',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'profileUser' => $profile,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'videoalbum' => $videoalbum,
            'videos' => $videos,
            'videosPageSize' => self::ALBUM_VIDEOS_LIMIT,
            'hasMoreVideos' => $permissions['video']
                ? $videoAlbums->hasMoreAlbumVideos($videoalbum, self::ALBUM_VIDEOS_LIMIT, 0)
                : false,
            'canManage' => $videoAlbums->isOwner($videoalbum, $viewer),
        ]);
    }

    /**
     * Показывает форму добавления видео в доступный альбом.
     *
     * @param VideoalbumRepository $videoAlbums
     * @return View|RedirectResponse
     */
    public function addVideo(VideoalbumRepository $videoAlbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $videoAlbums->ensureDefaultAlbum($viewer);

        return view('front.videoalbums.add-video', [
            'title' => 'Добавление видео',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'albums' => $videoAlbums->editableAlbumsFor($viewer),
        ]);
    }


    /**
     * Валидирует ссылку и добавляет видео в выбранный альбом.
     *
     * @param StoreVideoRequest $request
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function storeVideo(StoreVideoRequest $request, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $videoData = $request->toDto();

        $album = $videoAlbums->album($videoData->albumId);

        if (!$album || !$videoAlbums->isOwner($album, $viewer)) {
            abort(403);
        }

        try {
            $videoAlbums->addUserVideo($viewer, $album, $videoData);
        } catch (\RuntimeException $exception) {
            return back()
                ->withErrors(['video' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Показывает форму создания видеоальбома.
     */
    public function create(): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return view('front.videoalbums.form', [
            'title' => 'Создание видеоальбома',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'action' => route('front.videoalbums.store'),
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    /**
     * Создает видеоальбом из валидированных данных формы.
     *
     * @param AlbumRequest $request
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function store(AlbumRequest $request, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $albumData = $request->toDto();

        if ($videoAlbums->nameExists($viewer, $albumData->name)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $videoAlbums->createUserAlbum($viewer, $albumData);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Проверяет права и показывает форму редактирования видеоальбома.
     *
     * @param int $album
     * @param VideoalbumRepository $videoAlbums
     * @return View|RedirectResponse
     */
    public function edit(int $album, VideoalbumRepository $videoAlbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoAlbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoAlbums->isOwner($videoalbum, $viewer)) {
            abort(403);
        }

        return view('front.videoalbums.form', [
            'title' => 'Редактирование видеоальбома',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'videoalbum' => $videoalbum,
            'action' => route('front.videoalbums.update', ['album' => $videoalbum->id]),
            'name' => old('name', $videoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения видеоальбома
     *
     * @param int $album
     * @param AlbumRequest $request
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function update(int $album, AlbumRequest $request, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoAlbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoAlbums->isOwner($videoalbum, $viewer)) {
            abort(403);
        }

        $albumData = $request->toDto();

        if ($videoAlbums->nameExists($viewer, $albumData->name, $videoalbum->id)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $videoAlbums->updateUserAlbum($videoalbum, $albumData);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Проверяет права и удаляет видеоальбом.
     *
     * @param int $album
     * @param VideoalbumRepository $videoAlbums
     * @return RedirectResponse
     */
    public function destroy(int $album, VideoalbumRepository $videoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoAlbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoAlbums->isOwner($videoalbum, $viewer)) {
            abort(403);
        }

        $videoAlbums->deleteAlbum($videoalbum);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Готовит общую выдачу видеоальбомов для текущего или выбранного пользователя.
     *
     * @param int $userId
     * @param VideoalbumRepository $videoAlbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @param bool $showPopular
     * @return View
     */
    private function listing(
        int                  $userId,
        VideoalbumRepository $videoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
        bool                 $showPopular,
    ): View
    {
        $profile = $profiles->profile($userId);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $canManage = $viewer && (int)$viewer->id === (int)$profile->id;
        $videos = $permissions['video']
            ? $videoAlbums->videosForUser($profile->id, self::USER_VIDEOS_LIMIT, 0)
            : collect();

        return view('front.videoalbums.index', [
            'title' => 'Видеоальбомы',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'profileUser' => $profile,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'canManage' => $canManage,
            'showPopular' => $showPopular,
            'popularVideos' => $showPopular && $permissions['video']
                ? $videoAlbums->popularVideos(6, 0)
                : collect(),
            'albums' => $permissions['video'] ? $videoAlbums->albumsForUser($profile->id) : collect(),
            'videos' => $videos,
            'videosPageSize' => self::USER_VIDEOS_LIMIT,
            'hasMoreVideos' => $permissions['video']
                ? $videoAlbums->hasMoreUserVideos($profile->id, self::USER_VIDEOS_LIMIT, 0)
                : false,
        ]);
    }

    /**
     * Готовит данные верхнего блока профиля для страниц видеоальбомов.
     */
    private function profileLayout($user): array
    {
        return [
            'user' => $user,
            'avatar' => FrontAssets::userAvatar($user),
            'cover' => FrontAssets::userCover($user),
            'firstname' => $user->firstname ?: $user->displayName(),
            'lastname' => (string)$user->lastname,
            'about' => (string)$user->about,
        ];
    }
}
