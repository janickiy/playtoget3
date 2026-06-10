<?php

namespace App\Http\Controllers\Front;

use App\Helpers\FrontAssets;
use App\Http\Controllers\Controller;
use App\Repositories\FriendRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoalbumsController extends Controller
{
    private const USER_VIDEOS_LIMIT = 6;
    private const ALBUM_VIDEOS_LIMIT = 6;

    /**
     * Показывает видеоальбомы текущего пользователя.
     */
    public function index(
        VideoalbumRepository $videoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return $this->listing($viewer->id, $videoalbums, $profiles, $friends, true);
    }

    /**
     * Показывает видеоальбомы выбранного пользователя.
     */
    public function user(
        int                  $user,
        VideoalbumRepository $videoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        return $this->listing($user, $videoalbums, $profiles, $friends, false);
    }

    /**
     * Показывает видео выбранного видеоальбома.
     *
     * @param int $album
     * @param VideoalbumRepository $videoalbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function show(
        int                  $album,
        VideoalbumRepository $videoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        $videoalbum = $videoalbums->album($album);

        abort_if(!$videoalbum || !$videoalbum->owner, 404);

        $profile = $profiles->profile($videoalbum->owner_id);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $videos = $permissions['video']
            ? $videoalbums->albumVideos($videoalbum, self::ALBUM_VIDEOS_LIMIT, 0)
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
                ? $videoalbums->hasMoreAlbumVideos($videoalbum, self::ALBUM_VIDEOS_LIMIT, 0)
                : false,
            'canManage' => $videoalbums->isOwner($videoalbum, $viewer),
        ]);
    }

    /**
     * Показывает форму добавления видео в доступный альбом.
     *
     * @param VideoalbumRepository $videoalbums
     * @return View|RedirectResponse
     */
    public function addVideo(VideoalbumRepository $videoalbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $videoalbums->ensureDefaultAlbum($viewer);

        return view('front.videoalbums.add-video', [
            'title' => 'Добавление видео',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'albums' => $videoalbums->editableAlbumsFor($viewer),
        ]);
    }


    /**
     * Валидирует ссылку и добавляет видео в выбранный альбом.
     *
     * @param Request $request
     * @param VideoalbumRepository $videoalbums
     * @return RedirectResponse
     */
    public function storeVideo(Request $request, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $validated = $request->validate([
            'video' => ['required', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'videoalbum_id' => ['required', 'integer', 'min:1'],
        ], [
            'video.required' => 'Укажите ссылку на видео.',
            'videoalbum_id.required' => 'Выберите альбом.',
        ]);

        $album = $videoalbums->album((int)$validated['videoalbum_id']);

        if (!$album || !$videoalbums->isOwner($album, $viewer)) {
            abort(403);
        }

        try {
            $videoalbums->addUserVideo(
                $viewer,
                $album,
                $validated['video'],
                trim((string)($validated['description'] ?? '')),
            );
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
     * @param Request $request
     * @param VideoalbumRepository $videoalbums
     * @return RedirectResponse
     */
    public function store(Request $request, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Укажите название альбома.',
        ]);

        $name = trim($validated['name']);

        if ($videoalbums->nameExists($viewer, $name)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $videoalbums->createUserAlbum($viewer, $name);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Проверяет права и показывает форму редактирования видеоальбома.
     *
     * @param int $album
     * @param VideoalbumRepository $videoalbums
     * @return View|RedirectResponse
     */
    public function edit(int $album, VideoalbumRepository $videoalbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoalbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoalbums->isOwner($videoalbum, $viewer)) {
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
     * @param Request $request
     * @param VideoalbumRepository $videoalbums
     * @return RedirectResponse
     */
    public function update(int $album, Request $request, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoalbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoalbums->isOwner($videoalbum, $viewer)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Укажите название альбома.',
        ]);

        $name = trim($validated['name']);

        if ($videoalbums->nameExists($viewer, $name, $videoalbum->id)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $videoalbums->updateUserAlbum($videoalbum, $name);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Проверяет права и удаляет видеоальбом.
     *
     * @param int $album
     * @param VideoalbumRepository $videoalbums
     * @return RedirectResponse
     */
    public function destroy(int $album, VideoalbumRepository $videoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $videoalbum = $videoalbums->album($album);

        abort_if(!$videoalbum, 404);

        if (!$viewer || !$videoalbums->isOwner($videoalbum, $viewer)) {
            abort(403);
        }

        $videoalbums->deleteAlbum($videoalbum);

        return redirect()->route('front.videoalbums.index');
    }

    /**
     * Готовит общую выдачу видеоальбомов для текущего или выбранного пользователя.
     *
     * @param int $userId
     * @param VideoalbumRepository $videoalbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @param bool $showPopular
     * @return View
     */
    private function listing(
        int                  $userId,
        VideoalbumRepository $videoalbums,
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
            ? $videoalbums->videosForUser($profile->id, self::USER_VIDEOS_LIMIT, 0)
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
                ? $videoalbums->popularVideos(6, 0)
                : collect(),
            'albums' => $permissions['video'] ? $videoalbums->albumsForUser($profile->id) : collect(),
            'videos' => $videos,
            'videosPageSize' => self::USER_VIDEOS_LIMIT,
            'hasMoreVideos' => $permissions['video']
                ? $videoalbums->hasMoreUserVideos($profile->id, self::USER_VIDEOS_LIMIT, 0)
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
