<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Album\AlbumRequest;
use App\Http\Requests\Front\Community\CommunityRequest;
use App\Http\Requests\Front\Event\EventRequest;
use App\Http\Requests\Front\Video\StoreVideoRequest;
use App\Models\Community;
use App\Models\PhotoAlbums;
use App\Models\User;
use App\Models\VideoAlbums;
use App\Repositories\CommunityRepository;
use App\Repositories\EventRepository;
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
     * Shows list groups with filters and current user tabs.
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
            'title' => 'Groups',
            'myGroups' => $this->groupsForViewer($communities->myGroups($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'popularGroups' => $this->groupsForViewer($communities->popularGroups(self::PAGE_SIZE, 0, $filters, $viewer), $communities, $viewer),
            'invitedGroups' => $this->groupsForViewer($communities->invitedGroups($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'myGroupsTotal' => $communities->myGroupsCount($viewer->id, $filters),
            'popularGroupsTotal' => $communities->popularGroupsCount($filters, $viewer),
            'invitedGroupsTotal' => $communities->invitedGroupsCount($viewer->id, $filters),
            'groupsPageSize' => self::PAGE_SIZE,
            'viewer' => $viewer,
        ]);
    }

    /**
     * Shows groups selected user.
     *
     * @param int $user
     * @param CommunityRepository $communities
     * @return View
     */
    public function user(int $user, CommunityRepository $communities): View
    {
        return view('front.groups.index', [
            'title' => 'Groups user',
            'myGroups' => $this->groupsForViewer($communities->myGroups($user, 20), $communities, Auth::guard('web')->user()),
            'popularGroups' => collect(),
            'invitedGroups' => collect(),
            'viewer' => Auth::guard('web')->user(),
            'viewedUserId' => $user,
        ]);
    }

    /**
     * Checks authorization and shows form creation group.
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
            'title' => 'Create group',
            'action' => route('front.groups.store'),
            'button' => 'Create group',
            'group' => null,
            'settings' => null,
            'canEditSettings' => false,
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Validates data form and creates group.
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
     * Shows the group card, top block and comments.
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
     * Shows group members and rolls them.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function members(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        return view('front.groups.members', $this->groupPayload($group, $communities, 'members') + [
            'members' => $communities->members($group->id, $viewer?->id),
            'applications' => $communities->canManage($group, $viewer)
                ? $communities->applications($group->id)
                : collect(),
        ]);
    }

    /**
     * Checks permissions and shows form editing group.
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
            'title' => 'Edit group',
            'action' => route('front.groups.update', ['community' => $group->id]),
            'button' => 'Save',
            'group' => $group,
            'settings' => $communities->settings($group),
            'canEditSettings' => true,
            'admins' => $communities->admins($group->id),
            'blocked' => $communities->blocked($group->id),
        ]));
    }

    /**
     * Checks permissions and saves group changes
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
     * Shows photo albums group or current group.
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
        $canViewPhotos = $payload['permissions']['photo'];

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $canViewPhotos ? $photoAlbums->albumsForOwner($group->id, 'group') : collect(),
            'photos' => $canViewPhotos ? $photoAlbums->photosForOwner($group->id, 'group', self::PHOTOS_LIMIT, 0) : collect(),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $canViewPhotos ? $photoAlbums->hasMoreOwnerPhotos($group->id, 'group', self::PHOTOS_LIMIT, 0) : false,
            'popularPhotos' => $canViewPhotos ? $photoAlbums->popularPhotos(9, 0, 'group') : collect(),
        ]);
    }

    /**
     * Shows a photo from the selected group photo album.
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
        $canViewPhotos = $payload['permissions']['photo'];

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoAlbum,
            'photos' => $canViewPhotos ? $photoAlbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0) : collect(),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $canViewPhotos ? $photoAlbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0) : false,
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    /**
     * Shows the form for adding a photo to the group photo album.
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

        $photoAlbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Community album');

        return view('front.teams.photoalbums.add-photo', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Add photos',
            'albums' => $photoAlbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    /**
     * Shows the group photo album creation form.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createPhotoAlbum(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', array_merge($this->groupPayload($group, $communities, 'photoalbums'), [
            'title' => 'Create album',
            'formTitle' => 'Create album',
            'formTitleClass' => 'form-section-title',
            'action' => route('front.groups.photoalbums.store', ['community' => $group->id]),
            'name' => old('name', ''),
            'button' => 'Create',
        ]));
    }

    /**
     * Creates photo album group from validated data form.
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
            return back()->withErrors(['name' => 'An album with this name already exists.'])->withInput();
        }

        $photoAlbums->createAlbumForOwner($group->id, 'group', $albumData);

        return redirect()->route('front.groups.photoalbums', ['community' => $group->id]);
    }

    /**
     * Checks permissions and shows form editing photo album group.
     */
    public function editPhotoalbum(int $album, CommunityRepository $communities, PhotoalbumRepository $photoAlbums, ?int $community = null): View
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);
        abort_if(! $photoAlbum, 404);

        $group = $community ? $this->groupOrFail($community, $communities) : $this->groupOrFail((int) $photoAlbum->owner_id, $communities);
        $this->groupPhotoalbumOrFail($album, $group, $photoAlbums);
        abort_unless($communities->canManage($group, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', $this->groupPayload($group, $communities, 'photoalbums') + [
            'title' => 'Edit photo album',
            'action' => route('front.groups.photoalbum.update', ['album' => $photoAlbum->id]),
            'name' => old('name', $photoAlbum->name),
            'button' => 'Edit',
        ]);
    }

    /**
     * Checks permissions and saves changes to photo album group.
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
     * Checks permissions and deletes the group photo album.
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
     * Checks the group in the URL and deletes its photo album.
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
     * Shows the edit form for a specific group photo album.
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
     * Saves changes to a photo album of a specific group.
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
     * Shows a specific photo from the group photo album.
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
     * Shows photo group without reference to the selected album.
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
     * Shows video albums group or current group.
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
        $canViewVideos = $payload['permissions']['video'];

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
            'albums' => $canViewVideos ? $videoAlbums->albumsForOwner($group->id, 'group') : collect(),
            'videos' => $canViewVideos ? $videoAlbums->videosForOwner($group->id, 'group', self::VIDEOS_LIMIT, 0) : collect(),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $canViewVideos ? $videoAlbums->hasMoreOwnerVideos($group->id, 'group', self::VIDEOS_LIMIT, 0) : false,
            'popularVideos' => $canViewVideos ? $videoAlbums->popularVideos(6, 0, 'group') : collect(),
        ]);
    }

    /**
     * Shows video selected video album groups
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
        $canViewVideos = $payload['permissions']['video'];

        return view('front.teams.videoalbums.show', $payload + [
            'videoAlbum' => $videoAlbum,
            'videos' => $canViewVideos ? $videoAlbums->albumVideos($videoAlbum, self::VIDEOS_LIMIT, 0) : collect(),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $canViewVideos ? $videoAlbums->hasMoreAlbumVideos($videoAlbum, self::VIDEOS_LIMIT, 0) : false,
            'canManage' => $communities->canManage($group, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Shows the form for adding a video to the group video album.
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

        $videoAlbums->ensureDefaultAlbumForOwner($group->id, 'group', 'Community album');

        return view('front.teams.videoalbums.add-video', $this->groupPayload($group, $communities, 'videoalbums') + [
            'formTitle' => 'Add video',
            'albums' => $videoAlbums->editableAlbumsForOwner($group->id, 'group'),
        ]);
    }

    /**
     * Validates link and adds video to video album group.
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
     * Shows form creation video album group.
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
            'formTitle' => 'Create video album',
            'formTitleClass' => 'video-form-title',
            'action' => route('front.groups.videoalbums.store', ['community' => $group->id]),
            'name' => old('name', ''),
            'button' => 'Create',
        ]);
    }

    /**
     * Creates video album group from validated data forms
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
            return back()->withErrors(['name' => 'An album with this name already exists.'])->withInput();
        }

        $videoAlbums->createAlbumForOwner($group->id, 'group', $albumData);

        return redirect()->route('front.groups.videoalbums', ['community' => $group->id]);
    }

    /**
     * Checks permissions and shows form editing video album group.
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
            'formTitle' => 'Edit video album',
            'formTitleClass' => 'video-form-title',
            'action' => route('front.groups.videoalbum.update', ['album' => $videoAlbum->id]),
            'name' => old('name', $videoAlbum->name),
            'button' => 'Edit',
        ]);
    }

    /**
     * Checks permissions and saves changes to video album group.
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
     * Checks permissions and deletes the group video album.
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
     * Checks group in URL and deletes its video album.
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
     * Shows event group.
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
     * Shows the event creation form for a group.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createEvent(int $community, CommunityRepository $communities): View
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($viewer && $communities->canViewCommunityContent($group, $viewer), 403);

        return view('front.events.form', array_merge($this->groupPayload($group, $communities, 'events'), [
            'title' => 'Create event',
            'action' => route('front.groups.events.store', ['community' => $group->id]),
            'button' => 'Create event',
            'event' => null,
            'eventData' => null,
        ]));
    }

    /**
     * Creates an event and immediately binds it to the group.
     *
     * @param int $community
     * @param EventRequest $request
     * @param CommunityRepository $communities
     * @param EventRepository $events
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function storeEvent(int $community, EventRequest $request, CommunityRepository $communities, EventRepository $events): RedirectResponse
    {
        $group = $this->groupOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($viewer && $communities->canViewCommunityContent($group, $viewer), 403);

        $event = $events->createEvent($viewer, $request->toDto());
        $communities->changeEventMembership($group, $event->id, 1);

        return redirect()->route('front.groups.events', ['community' => $group->id]);
    }

    /**
     * Prepares common data groups for nested sections pages.
     */
    private function groupPayload(Community $group, CommunityRepository $communities, string $section): array
    {
        $viewer = Auth::guard('web')->user();
        $groupData = $communities->serializeGroup($group);
        $role = $communities->role($group->id, $viewer?->id);
        $membershipType = $communities->membershipType($group, $viewer);
        $accessDenied = ! $communities->canViewCommunityContent($group, $viewer);
        $permissions = $communities->permissions($group, $viewer);

        if ($accessDenied) {
            $permissions = [
                'wall' => false,
                'photo' => false,
                'video' => false,
            ];
        }

        $sectionPermission = $this->sectionPermissionKey($section);
        $sectionAccessDenied = ! $accessDenied
            && $sectionPermission !== null
            && ! (bool) ($permissions[$sectionPermission] ?? true);

        return [
            'title' => $group->name ?: 'Group',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'group' => $group,
            'groupData' => $groupData,
            'team' => $group,
            'teamData' => $groupData,
            'permissions' => $permissions,
            'role' => $role,
            'membershipType' => $membershipType,
            'canManageGroup' => $communities->canManage($group, $viewer),
            'canManageTeam' => $communities->canManage($group, $viewer),
            'canInviteGroup' => $communities->canInvite($group, $viewer),
            'canInviteTeam' => $communities->canInvite($group, $viewer),
            'communityAccessDenied' => $accessDenied,
            'communityAccessMessage' => $membershipType === 'blocked' ? 'Access to this page is restricted' : 'This is a closed group',
            'sectionAccessDenied' => $sectionAccessDenied,
            'sectionAccessMessage' => $this->sectionAccessMessage($sectionPermission, 'group'),
            'section' => $section,
            'communityView' => [
                'kind' => 'group',
                'route' => 'front.groups',
                'top' => 'front.groups._top',
                'label' => 'Group',
                'labelLower' => 'group',
                'labelGenitive' => 'group',
                'pluralGenitive' => 'groups',
                'entity' => $group,
                'data' => $groupData,
            ],
        ];
    }

    /**
     * Returns privacy setting key for current section group.
     */
    private function sectionPermissionKey(string $section): ?string
    {
        return match ($section) {
            'feed' => 'wall',
            'photoalbums' => 'photo',
            'videoalbums' => 'video',
            default => null,
        };
    }

    /**
     * Returns message text for the closed section group.
     */
    private function sectionAccessMessage(?string $sectionPermission, string $labelGenitive): string
    {
        return match ($sectionPermission) {
            'wall' => 'Feed ' . $labelGenitive . ' is hidden by privacy settings.',
            'photo' => 'Photos ' . $labelGenitive . ' are hidden by privacy settings.',
            'video' => 'Video ' . $labelGenitive . ' is hidden by privacy settings.',
            default => 'Section is hidden by privacy settings.',
        };
    }

    /**
     * Adds to the list of groups data about permissions and current user status.
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
     * Collects group list filters from query parameters.
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
     * Detects group from route parameter or from current user.
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
     * Finds active group or ends the request with a 404 error.
     */
    private function groupOrFail(int $community, CommunityRepository $communities): Community
    {
        $group = $communities->findGroup($community);

        abort_if(! $group, 404);

        return $group;
    }

    /**
     * Finds a photo album that belongs to the group or aborts with a 404 error.
     */
    private function groupPhotoalbumOrFail(int $album, Community $group, PhotoalbumRepository $photoAlbums): PhotoAlbums
    {
        $photoAlbum = $photoAlbums->album($album, ['group']);

        abort_if(! $photoAlbum || (int) $photoAlbum->owner_id !== (int) $group->id, 404);

        return $photoAlbum;
    }

    /**
     * Finds a video album that belongs to the group or aborts with a 404 error.
     */
    private function groupVideoalbumOrFail(int $album, Community $group, VideoalbumRepository $videoAlbums): VideoAlbums
    {
        $videoAlbum = $videoAlbums->album($album, ['group']);

        abort_if(! $videoAlbum || (int) $videoAlbum->owner_id !== (int) $group->id, 404);

        return $videoAlbum;
    }
}
