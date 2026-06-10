<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Repositories\FriendRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoalbumsController extends Controller
{
    private const USER_PHOTOS_LIMIT = 6;
    private const ALBUM_PHOTOS_LIMIT = 9;

    /**
     * Показывает фотоальбомы текущего пользователя.
     */
    public function index(
        PhotoalbumRepository $photoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return $this->listing($viewer->id, $photoalbums, $profiles, $friends, true);
    }

    /**
     * Показывает фотоальбомы выбранного пользователя.
     *
     * @param int $user
     * @param PhotoalbumRepository $photoalbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function user(
        int                  $user,
        PhotoalbumRepository $photoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        return $this->listing($user, $photoalbums, $profiles, $friends, false);
    }

    /**
     * Показывает фотографии выбранного фотоальбома.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoalbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function show(
        int                  $album,
        PhotoalbumRepository $photoalbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        $photoalbum = $photoalbums->album($album);

        abort_if(!$photoalbum || !$photoalbum->owner, 404);

        $profile = $profiles->profile($photoalbum->owner_id);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $photos = $permissions['photo']
            ? $photoalbums->albumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0)
            : collect();

        return view('front.photoalbums.show', [
            'title' => 'Фотоальбомы',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'profileUser' => $profile,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'photoalbum' => $photoalbum,
            'photos' => $photos,
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $permissions['photo']
                ? $photoalbums->hasMoreAlbumPhotos($photoalbum, self::ALBUM_PHOTOS_LIMIT, 0)
                : false,
            'canManage' => $photoalbums->isOwner($photoalbum, $viewer),
        ]);
    }

    /**
     * Показывает форму добавления фотографии в доступный альбом.
     *
     * @param PhotoalbumRepository $photoalbums
     * @return View|RedirectResponse
     */
    public function addPhoto(PhotoalbumRepository $photoalbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $photoalbums->ensureDefaultAlbum($viewer);

        return view('front.photoalbums.add-photo', [
            'title' => 'Добавление фото',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'albums' => $photoalbums->editableAlbumsFor($viewer),
        ]);
    }

    /**
     * Показывает форму создания фотоальбома.
     */
    public function create(): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return view('front.photoalbums.form', [
            'title' => 'Создание фотоальбома',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'action' => route('front.photoalbums.store'),
            'method' => 'POST',
            'name' => old('name', ''),
            'button' => 'Создать',
        ]);
    }

    /**
     * Создает фотоальбом из валидированных данных формы.
     *
     * @param Request $request
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function store(Request $request, PhotoalbumRepository $photoalbums): RedirectResponse
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

        if ($photoalbums->nameExists($viewer, $name)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoalbums->createUserAlbum($viewer, $name);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Проверяет права и показывает форму редактирования фотоальбома.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoalbums
     * @return View|RedirectResponse
     */
    public function edit(int $album, PhotoalbumRepository $photoalbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoalbum = $photoalbums->album($album);

        abort_if(!$photoalbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoalbum, $viewer)) {
            abort(403);
        }

        return view('front.photoalbums.form', [
            'title' => 'Редактирование фотоальбома',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'photoalbum' => $photoalbum,
            'action' => route('front.photoalbums.update', ['album' => $photoalbum->id]),
            'method' => 'POST',
            'name' => old('name', $photoalbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения фотоальбома.
     *
     * @param int $album
     * @param Request $request
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function update(int $album, Request $request, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoalbum = $photoalbums->album($album);

        abort_if(!$photoalbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoalbum, $viewer)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Укажите название альбома.',
        ]);

        $name = trim($validated['name']);

        if ($photoalbums->nameExists($viewer, $name, $photoalbum->id)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoalbums->updateUserAlbum($photoalbum, $name);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Проверяет права и удаляет фотоальбом.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function destroy(int $album, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoalbum = $photoalbums->album($album);

        abort_if(!$photoalbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoalbum, $viewer)) {
            abort(403);
        }

        $photoalbums->deleteAlbum($photoalbum);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Готовит общую выдачу фотоальбомов для текущего или выбранного пользователя.
     *
     * @param int $userId
     * @param PhotoalbumRepository $photoalbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @param bool $showPopular
     * @return View
     */
    private function listing(
        int                  $userId,
        PhotoalbumRepository $photoalbums,
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
        $photos = $permissions['photo']
            ? $photoalbums->photosForUser($profile->id, self::USER_PHOTOS_LIMIT, 0)
            : collect();

        return view('front.photoalbums.index', [
            'title' => 'Фотоальбомы',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'profileUser' => $profile,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'canManage' => $canManage,
            'showPopular' => $showPopular,
            'popularPhotos' => $showPopular && $permissions['photo']
                ? $photoalbums->popularPhotos(9, 0)
                : collect(),
            'albums' => $permissions['photo'] ? $photoalbums->albumsForUser($profile->id) : collect(),
            'photos' => $photos,
            'photosPageSize' => self::USER_PHOTOS_LIMIT,
            'hasMorePhotos' => $permissions['photo']
                ? $photoalbums->hasMoreUserPhotos($profile->id, self::USER_PHOTOS_LIMIT, 0)
                : false,
        ]);
    }

    /**
     * Готовит данные верхнего блока профиля для страниц фотоальбомов.
     *
     * @param $user
     * @return array
     */
    private function profileLayout($user): array
    {
        return [
            'user' => $user,
            'avatar' => \App\Helpers\FrontAssets::userAvatar($user),
            'cover' => \App\Helpers\FrontAssets::userCover($user),
            'firstname' => $user->firstname ?: $user->displayName(),
            'lastname' => (string)$user->lastname,
            'about' => (string)$user->about,
        ];
    }
}
