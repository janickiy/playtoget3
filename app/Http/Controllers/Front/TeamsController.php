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

class TeamsController extends Controller
{
    private const PAGE_SIZE = 5;
    private const PHOTOS_LIMIT = 6;
    private const ALBUM_PHOTOS_LIMIT = 9;
    private const VIDEOS_LIMIT = 6;
    private const COMMENTS_LIMIT = 10;


    /**
     * Shows list teams with filters and current user tabs.
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
            'title' => 'Teams',
            'myTeams' => $this->teamsForViewer($communities->myTeams($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'popularTeams' => $this->teamsForViewer($communities->popularTeams(self::PAGE_SIZE, 0, $filters, $viewer), $communities, $viewer),
            'invitedTeams' => $this->teamsForViewer($communities->invitedTeams($viewer->id, self::PAGE_SIZE, 0, $filters), $communities, $viewer),
            'myTeamsTotal' => $communities->myTeamsCount($viewer->id, $filters),
            'popularTeamsTotal' => $communities->popularTeamsCount($filters, $viewer),
            'invitedTeamsTotal' => $communities->invitedTeamsCount($viewer->id, $filters),
            'teamsPageSize' => self::PAGE_SIZE,
            'viewer' => $viewer,
        ]);
    }

    /**
     * Shows teams selected user.
     *
     * @param int $user
     * @param CommunityRepository $communities
     * @return View
     */
    public function user(int $user, CommunityRepository $communities): View
    {
        return view('front.teams.index', [
            'title' => 'Teams user',
            'myTeams' => $this->teamsForViewer($communities->myTeams($user, 20), $communities, Auth::guard('web')->user()),
            'popularTeams' => collect(),
            'invitedTeams' => collect(),
            'viewer' => Auth::guard('web')->user(),
            'viewedUserId' => $user,
        ]);
    }

    /**
     * Checks authorization and shows form creation team.
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
            'title' => 'Create team',
            'action' => route('front.teams.store'),
            'button' => 'Create team',
            'team' => null,
            'settings' => null,
            'canEditSettings' => false,
            'hideTopProfile' => true,
        ]);
    }

    /**
     * Validates the data form and creates a team.
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
     * Shows team card, top block and comments.
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
     * Shows team members and rolls them.
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
     * Checks permissions and shows form editing team.
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
            'title' => 'Edit team',
            'action' => route('front.teams.update', ['community' => $team->id]),
            'button' => 'Save',
            'team' => $team,
            'settings' => $communities->settings($team),
            'canEditSettings' => true,
            'admins' => $communities->admins($team->id),
            'blocked' => $communities->blocked($team->id),
        ]));
    }

    /**
     * Checks permissions and saves changes team.
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
     * Shows photo albums team or current team.
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
        $canViewPhotos = $payload['permissions']['photo'];

        return view('front.teams.photoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $canViewPhotos ? $photoAlbums->albumsForOwner($team->id, 'team') : collect(),
            'photos' => $canViewPhotos ? $photoAlbums->photosForOwner($team->id, 'team', self::PHOTOS_LIMIT, 0) : collect(),
            'photosPageSize' => self::PHOTOS_LIMIT,
            'hasMorePhotos' => $canViewPhotos ? $photoAlbums->hasMoreOwnerPhotos($team->id, 'team', self::PHOTOS_LIMIT, 0) : false,
            'popularPhotos' => $canViewPhotos ? $photoAlbums->popularPhotos(9, 0, 'team') : collect(),
        ]);
    }

    /**
     * Shows photo selected photo albumа team.
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
        $canViewPhotos = $payload['permissions']['photo'];

        return view('front.teams.photoalbums.show', $payload + [
            'photoalbum' => $photoAlbum,
            'photos' => $canViewPhotos ? $photoAlbums->albumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0) : collect(),
            'photosPageSize' => self::ALBUM_PHOTOS_LIMIT,
            'hasMorePhotos' => $canViewPhotos ? $photoAlbums->hasMoreAlbumPhotos($photoAlbum, self::ALBUM_PHOTOS_LIMIT, 0) : false,
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'openPhotoId' => null,
        ]);
    }

    /**
     * Shows form adding photo в photo album team.
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

        $photoAlbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Community album');

        return view('front.teams.photoalbums.add-photo', $this->teamPayload($team, $communities, 'photoalbums') + [
            'title' => 'Add photos',
            'albums' => $photoAlbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    /**
     * Shows form creation photo albumа team.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createPhotoAlbum(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        abort_unless($communities->canManage($team, Auth::guard('web')->user()), 403);

        return view('front.teams.album-form', array_merge($this->teamPayload($team, $communities, 'photoalbums'), [
            'title' => 'Create album',
            'formTitle' => 'Create album',
            'formTitleClass' => 'form-section-title',
            'action' => route('front.teams.photoalbums.store', ['community' => $team->id]),
            'name' => old('name', ''),
            'button' => 'Create',
        ]));
    }

    /**
     * Creates photo album team from validated data forms.
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
            return back()->withErrors(['name' => 'An album with this name already exists.'])->withInput();
        }

        $photoAlbums->createAlbumForOwner($team->id, 'team', $albumData);

        return redirect()->route('front.teams.photoalbums', ['community' => $team->id]);
    }

    /**
     * Checks permissions and shows the team's editing photo album form.
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
            'title' => 'Edit photo album',
            'action' => route('front.teams.photoalbum.update', ['album' => $photoAlbum->id]),
            'name' => old('name', $photoAlbum->name),
            'button' => 'Edit',
        ]);
    }

    /**
     * Checks permissions and saves changes to photo album team.
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
     * Checks permissions и deletes photo album team.
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
     * Shows form editing photo album of a specific team
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
     * Saves changes to a specific team's photo album.
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
     * Shows specific photo из photo albumа team.
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
     * Shows photo team without reference to the selected album.
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
     * Shows video albums team or current team.
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
        $canViewVideos = $payload['permissions']['video'];

        return view('front.teams.videoalbums.index', $payload + [
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
            'albums' => $canViewVideos ? $videoAlbums->albumsForOwner($team->id, 'team') : collect(),
            'videos' => $canViewVideos ? $videoAlbums->videosForOwner($team->id, 'team', self::VIDEOS_LIMIT, 0) : collect(),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $canViewVideos ? $videoAlbums->hasMoreOwnerVideos($team->id, 'team', self::VIDEOS_LIMIT, 0) : false,
            'popularVideos' => $canViewVideos ? $videoAlbums->popularVideos(6, 0, 'team') : collect(),
        ]);
    }

    /**
     * Shows video selected video album team.
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
        $canViewVideos = $payload['permissions']['video'];

        return view('front.teams.videoalbums.show', $payload + [
            'videoAlbum' => $videoAlbum,
            'videos' => $canViewVideos ? $videoAlbums->albumVideos($videoAlbum, self::VIDEOS_LIMIT, 0) : collect(),
            'videosPageSize' => self::VIDEOS_LIMIT,
            'hasMoreVideos' => $canViewVideos ? $videoAlbums->hasMoreAlbumVideos($videoAlbum, self::VIDEOS_LIMIT, 0) : false,
            'canManage' => $communities->canManage($team, Auth::guard('web')->user()),
        ]);
    }

    /**
     * Shows form adding video в video album team.
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

        $videoAlbums->ensureDefaultAlbumForOwner($team->id, 'team', 'Community album');

        return view('front.teams.videoalbums.add-video', $this->teamPayload($team, $communities, 'videoalbums') + [
            'formTitle' => 'Add video',
            'albums' => $videoAlbums->editableAlbumsForOwner($team->id, 'team'),
        ]);
    }

    /**
     * Validates the link and adds video to the video album team.
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
     * Shows form creation video album team.
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
            'formTitle' => 'Create video album',
            'formTitleClass' => 'video-form-title',
            'action' => route('front.teams.videoalbums.store', ['community' => $team->id]),
            'name' => old('name', ''),
            'button' => 'Create',
        ]);
    }

    /**
     * Creates video album team from validated data forms.
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
            return back()->withErrors(['name' => 'An album with this name already exists.'])->withInput();
        }

        $videoAlbums->createAlbumForOwner($team->id, 'team', $albumData);

        return redirect()->route('front.teams.videoalbums', ['community' => $team->id]);
    }

    /**
     * Checks permissions and shows form editing video album team.
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
            'formTitle' => 'Edit video album',
            'formTitleClass' => 'video-form-title',
            'action' => route('front.teams.videoalbum.update', ['album' => $videoAlbum->id]),
            'name' => old('name', $videoAlbum->name),
            'button' => 'Edit',
        ]);
    }

    /**
     * Checks permissions and saves changes video album team
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
     * Checks permissions и deletes video album team.
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
     * Shows event team.
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
     * Shows form creation event для team.
     *
     * @param int $community
     * @param CommunityRepository $communities
     * @return View
     */
    public function createEvent(int $community, CommunityRepository $communities): View
    {
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($viewer && $communities->canViewCommunityContent($team, $viewer), 403);

        return view('front.events.form', array_merge($this->teamPayload($team, $communities, 'events'), [
            'title' => 'Create event',
            'action' => route('front.teams.events.store', ['community' => $team->id]),
            'button' => 'Create event',
            'event' => null,
            'eventData' => null,
        ]));
    }

    /**
     * Creates an event and immediately binds it to teams.
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
        $team = $this->teamOrFail($community, $communities);
        $viewer = Auth::guard('web')->user();

        abort_unless($viewer && $communities->canViewCommunityContent($team, $viewer), 403);

        $event = $events->createEvent($viewer, $request->toDto());
        $communities->changeEventMembership($team, $event->id, 1);

        return redirect()->route('front.teams.events', ['community' => $team->id]);
    }

    /**
     * Prepares general data team for nested sections pages.
     *
     * @param Community $team
     * @param CommunityRepository $communities
     * @param string $section
     * @return array
     */
    private function teamPayload(Community $team, CommunityRepository $communities, string $section): array
    {
        $viewer = Auth::guard('web')->user();
        $role = $communities->role($team->id, $viewer?->id);
        $membershipType = $communities->membershipType($team, $viewer);
        $accessDenied = ! $communities->canViewCommunityContent($team, $viewer);
        $permissions = $communities->permissions($team, $viewer);

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
            'title' => $team->name ?: 'Team',
            'hideTopProfile' => true,
            'viewer' => $viewer,
            'team' => $team,
            'teamData' => $communities->serializeTeam($team),
            'permissions' => $permissions,
            'role' => $role,
            'membershipType' => $membershipType,
            'canManageTeam' => $communities->canManage($team, $viewer),
            'canInviteTeam' => $communities->canInvite($team, $viewer),
            'communityAccessDenied' => $accessDenied,
            'communityAccessMessage' => $membershipType === 'blocked' ? 'Access to this page is restricted' : 'This is a closed team',
            'sectionAccessDenied' => $sectionAccessDenied,
            'sectionAccessMessage' => $this->sectionAccessMessage($sectionPermission, 'team'),
            'section' => $section,
        ];
    }

    /**
     * Returns privacy settings key for the current section team.
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
     * Returns message text for the closed section team.
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
     * Adds to the list of teams data about permissions and status of the current user.
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
     * Collects filters for the teams list from query parameters.
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
     * Detects team from route parameter or from current user.
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
     * Finds active team or ends the request with a 404 error.
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
     * Finds photo album owned by teams or fails with a 404 error
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
     * Finds video album owned by teams or fails the request with a 404 error
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
