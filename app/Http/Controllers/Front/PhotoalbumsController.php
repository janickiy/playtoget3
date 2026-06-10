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
        PhotoalbumRepository $photoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        return $this->listing($viewer->id, $photoAlbums, $profiles, $friends, true);
    }

    /**
     * Показывает фотоальбомы выбранного пользователя.
     *
     * @param int $user
     * @param PhotoalbumRepository $photoAlbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function user(
        int                  $user,
        PhotoalbumRepository $photoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        return $this->listing($user, $photoAlbums, $profiles, $friends, false);
    }

    /**
     * Показывает фотографии выбранного фотоальбома.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoAlbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @return View
     */
    public function show(
        int                  $album,
        PhotoalbumRepository $photoAlbums,
        ProfileRepository    $profiles,
        FriendRepository     $friends,
    ): View
    {
        $photoAlbum = $photoAlbums->album($album);

        abort_if(!$photoAlbum || !$photoAlbum->owner, 404);

        $profile = $profiles->profile($photoAlbum->owner_id);

        abort_if(!$profile, 404);

        $viewer = Auth::guard('web')->user();
        $friendshipStatus = $friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $profiles->permissions($profile, $viewer, $friendshipStatus);
        $photos = $permissions['photo']
            ? $photoAlbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0)
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
                ? $photoAlbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0)
                : false,
            'canManage' => $photoAlbums->isOwner($photoAlbum, $viewer),
        ]);
    }

    /**
     * Показывает форму добавления фотографии в доступный альбом.
     *
     * @param PhotoalbumRepository $photoAlbums
     * @return View|RedirectResponse
     */
    public function addPhoto(PhotoalbumRepository $photoAlbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $photoAlbums->ensureDefaultAlbum($viewer);

        return view('front.photoalbums.add-photo', [
            'title' => 'Добавление фото',
            'viewer' => $viewer,
            'profileLayout' => $this->profileLayout($viewer),
            'albums' => $photoAlbums->editableAlbumsFor($viewer),
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
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function store(AlbumRequest $request, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();

        if (!$viewer) {
            return redirect()->route('front.home');
        }

        $albumData = $request->toDto();

        if ($photoAlbums->nameExists($viewer, $albumData->name)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoAlbums->createUserAlbum($viewer, $albumData);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Проверяет права и показывает форму редактирования фотоальбома.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoAlbums
     * @return View|RedirectResponse
     */
    public function edit(int $album, PhotoalbumRepository $photoAlbums): View|RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoAlbum = $photoAlbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoAlbums->isOwner($photoAlbum, $viewer)) {
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
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function update(int $album, AlbumRequest $request, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoAlbum = $photoAlbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoAlbums->isOwner($photoAlbum, $viewer)) {
            abort(403);
        }

        $albumData = $request->toDto();

        if ($photoAlbums->nameExists($viewer, $albumData->name, $photoAlbum->id)) {
            return back()
                ->withErrors(['name' => 'Альбом с таким названием уже существует.'])
                ->withInput();
        }

        $photoAlbums->updateUserAlbum($photoAlbum, $albumData);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Проверяет права и удаляет фотоальбом.
     *
     * @param int $album
     * @param PhotoalbumRepository $photoAlbums
     * @return RedirectResponse
     */
    public function destroy(int $album, PhotoalbumRepository $photoAlbums): RedirectResponse
    {
        $viewer = Auth::guard('web')->user();
        $photoAlbum = $photoAlbums->album($album);

        abort_if(!$photoAlbum, 404);

        if (!$viewer || !$photoAlbums->isOwner($photoAlbum, $viewer)) {
            abort(403);
        }

        $photoAlbums->deleteAlbum($photoAlbum);

        return redirect()->route('front.photoalbums.index');
    }

    /**
     * Готовит общую выдачу фотоальбомов для текущего или выбранного пользователя.
     *
     * @param int $userId
     * @param PhotoalbumRepository $photoAlbums
     * @param ProfileRepository $profiles
     * @param FriendRepository $friends
     * @param bool $showPopular
     * @return View
     */
    private function listing(
        int                  $userId,
        PhotoalbumRepository $photoAlbums,
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
            ? $photoAlbums->photosForUser($profile->id, self::USER_PHOTOS_LIMIT, 0)
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
                ? $photoAlbums->popularPhotos(9, 0)
                : collect(),
            'albums' => $permissions['photo'] ? $photoAlbums->albumsForUser($profile->id) : collect(),
            'photos' => $photos,
            'photosPageSize' => self::USER_PHOTOS_LIMIT,
            'hasMorePhotos' => $permissions['photo']
                ? $photoAlbums->hasMoreUserPhotos($profile->id, self::USER_PHOTOS_LIMIT, 0)
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
