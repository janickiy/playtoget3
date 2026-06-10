<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Album\AlbumRequest;
use App\Repositories\FriendRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
        $photoAlbum = $photoalbums->album($album);

        abort_if(!$photoAlbum || !$photoAlbum->owner, 404);

        $profile = $profiles->profile($photoAlbum->owner_id);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $photos = $permissions['photo']
            ? $photoalbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0)
            : collect();

        return view('front.photoalbums.show', [
            'title' => 'Фотоальбомы',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'profileUser' => $profile,
            'profileData' => $profiles->profileData($profile),
            'permissions' => $permissions,
            'friendshipStatus' => $friendshipStatus,
            'photoalbum' => $photoAlbum,
            'photos' => $photos,
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $permissions['photo']
                ? $photoalbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0)
                : false,
            'canManage' => $photoalbums->isOwner($photoAlbum, $viewer),
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
     * @param AlbumRequest $request
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function store(AlbumRequest $request, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $albumData = $request->toDto();

        if ($photoalbums->nameExists($viewer, $albumData->name)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoalbums->createUserAlbum($viewer, $albumData);

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
        $photoAlbum = $photoalbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoAlbum, $viewer)) {
            abort(403);
        }

        return view('front.photoalbums.form', [
            'title' => 'Редактирование фотоальбома',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'photoalbum' => $photoAlbum,
            'action' => route('front.photoalbums.update', ['album' => $photoAlbum->id]),
            'method' => 'POST',
            'name' => old('name', $photoAlbum->name),
            'button' => 'Редактировать',
        ]);
    }

    /**
     * Проверяет права и сохраняет изменения фотоальбома.
     *
     * @param int $album
     * @param AlbumRequest $request
     * @param PhotoalbumRepository $photoalbums
     * @return RedirectResponse
     */
    public function update(int $album, AlbumRequest $request, PhotoalbumRepository $photoalbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoAlbum = $photoalbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoAlbum, $viewer)) {
            abort(403);
        }

        $albumData = $request->toDto();

        if ($photoalbums->nameExists($viewer, $albumData->name, $photoAlbum->id)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoalbums->updateUserAlbum($photoAlbum, $albumData);

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
        $photoAlbum = $photoalbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoalbums->isOwner($photoAlbum, $viewer)) {
            abort(403);
        }

        $photoalbums->deleteAlbum($photoAlbum);

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
