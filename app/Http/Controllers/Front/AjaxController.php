<?php

namespace App\Http\Controllers\Front;

use App\Helpers\FrontAssets;
use App\Http\Controllers\Controller;
use App\Models\GeoCity;
use App\Models\Like;
use App\Models\Photo;
use App\Models\Photoalbum;
use App\Models\Share;
use App\Models\SportType;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Repositories\FriendRepository;
use App\Repositories\CommunityRepository;
use App\Repositories\EventRepository;
use App\Repositories\MessageRepository;
use App\Repositories\NewsRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\SportBlockRepository;
use App\Repositories\UserRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AjaxController extends Controller
{
    public function __construct(
        private readonly NewsRepository    $news,
        private readonly FriendRepository  $friends,
        private readonly UserRepository    $users,
        private readonly ProfileRepository $profiles,
        private readonly MessageRepository $messages,
        private readonly PhotoalbumRepository $photoalbums,
        private readonly VideoalbumRepository $videoalbums,
        private readonly CommunityRepository $communities,
        private readonly EventRepository $events,
        private readonly SportBlockRepository $sportBlocks,
    )
    {
    }

    public function handle(Request $request, string $action): JsonResponse
    {
        return match ($action) {
            'get_usernews_list' => $this->getUserNewsList($request),
            'get_communities_list' => $this->getCommunitiesList($request),
            'get_pop_communities_list' => $this->getPopCommunitiesList($request),
            'get_sport_blocks_list' => $this->getSportBlocksList($request),
            'get_events_list' => $this->getEventsList($request),
            'get_pop_events_list' => $this->getPopEventsList($request),
            'getpossiblefriends' => $this->getPossibleFriends($request),
            'get_friends_list' => $this->getFriendsList($request),
            'add_as_friend' => $this->addAsFriend($request),
            'accept_friendship' => $this->acceptFriendship($request),
            'remove_friend' => $this->removeFriend($request),
            'block_user' => $this->blockUser($request),
            'unblock_user' => $this->unblockUser($request),
            'changememberstatus' => $this->changeCommunityMemberStatus($request),
            'send_community_invitation' => $this->sendCommunityInvitation($request),
            'search_event' => $this->searchEvent($request),
            'change_event_community_status' => $this->changeEventCommunityStatus($request),
            'change_event_memberstatus' => $this->changeEventMemberStatus($request),
            'send_event_invitation' => $this->sendEventInvitation($request),
            'getcomments' => $this->getComments($request),
            'getphotoinfo' => $this->getPhotoInfo($request),
            'add_photo_ajax' => $this->addPhotoAjax($request),
            'add_photo_ajax_attach' => $this->addPhotoAjaxAttach($request),
            'get_photos_list' => $this->getPhotosList($request),
            'get_album_photos' => $this->getAlbumPhotos($request),
            'removepic' => $this->removePic($request),
            'getvideoinfo' => $this->getVideoInfo($request),
            'get_videos_list' => $this->getVideosList($request),
            'get_album_videos' => $this->getAlbumVideos($request),
            'removevideo' => $this->removeVideo($request),
            'uploadavatar' => $this->uploadAvatar($request),
            'uploadcover' => $this->uploadCover($request),
            'addcomment' => $this->addComment($request),
            'removecomment' => $this->removeComment($request),
            'addmessage' => $this->addMessage($request),
            'getmessages' => $this->getMessages($request),
            'get_new_messages' => $this->getNewMessages($request),
            'remove_message' => $this->removeMessage($request),
            'remove_dialog' => $this->removeDialog($request),
            'liked' => $this->liked($request),
            'shared' => $this->shared($request),
            'search_city' => $this->searchCity($request),
            'search_sport_types' => $this->searchSportTypes($request),
            default => response()->json([
                'action' => $action,
                'status' => 'not_implemented',
                'payload' => $request->except(['_token']),
            ]),
        };
    }

    private function getUserNewsList(Request $request): JsonResponse
    {
        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $news = $this->news->feedPage($limit, $offset);
        $hasMore = $this->news->feedPage($limit, $offset + $limit)->isNotEmpty();

        return response()->json([
            'status' => 1,
            'html' => view('front.news._items', ['news' => $news])->render(),
            'count' => $news->count(),
            'has_more' => $hasMore,
        ]);
    }

    private function getCommunitiesList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'html' => ''], 401);
        }

        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $userId = max((int)$request->input('user_id', $viewer->id), 1);
        $type = (string)$request->input('type', 'group');
        $feed = (string)$request->input('feed', 'mygroups');
        $filters = $this->communityFilters($request);

        if (!in_array($type, ['group', 'team'], true)) {
            return response()->json(['status' => 0, 'html' => ''], 422);
        }

        if ($feed === 'invited') {
            $items = $type === 'team'
                ? $this->communities->invitedTeams($userId, $limit, $offset, $filters)
                : $this->communities->invitedGroups($userId, $limit, $offset, $filters);
            $nextItems = $type === 'team'
                ? $this->communities->invitedTeams($userId, 1, $offset + $limit, $filters)
                : $this->communities->invitedGroups($userId, 1, $offset + $limit, $filters);
        } else {
            $items = $type === 'team'
                ? $this->communities->myTeams($userId, $limit, $offset, $filters)
                : $this->communities->myGroups($userId, $limit, $offset, $filters);
            $nextItems = $type === 'team'
                ? $this->communities->myTeams($userId, 1, $offset + $limit, $filters)
                : $this->communities->myGroups($userId, 1, $offset + $limit, $filters);
        }

        return response()->json([
            'status' => 1,
            'html' => $this->renderCommunities($this->communitiesForViewer($items, $viewer), $type),
            'count' => $items->count(),
            'has_more' => $nextItems->isNotEmpty(),
        ]);
    }

    private function getPopCommunitiesList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'html' => ''], 401);
        }

        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $type = (string)$request->input('type', 'group');
        $filters = $this->communityFilters($request);

        if (!in_array($type, ['group', 'team'], true)) {
            return response()->json(['status' => 0, 'html' => ''], 422);
        }

        $items = $type === 'team'
            ? $this->communities->popularTeams($limit, $offset, $filters)
            : $this->communities->popularGroups($limit, $offset, $filters);
        $nextItems = $type === 'team'
            ? $this->communities->popularTeams(1, $offset + $limit, $filters)
            : $this->communities->popularGroups(1, $offset + $limit, $filters);

        return response()->json([
            'status' => 1,
            'html' => $this->renderCommunities($this->communitiesForViewer($items, $viewer), $type),
            'count' => $items->count(),
            'has_more' => $nextItems->isNotEmpty(),
        ]);
    }

    private function getSportBlocksList(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('number', 5), 1), 25);
        $offset = max((int) $request->input('offset', 0), 0);
        $type = (string) $request->input('type', 'playground');
        $routePrefix = match ($type) {
            'playground' => 'front.playgrounds',
            'shop' => 'front.shops',
            'fitness' => 'front.fitness',
            default => null,
        };

        if (! $routePrefix) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false], 422);
        }

        $filters = $this->sportBlockFilters($request);
        $items = $this->sportBlocks->serializedByType($type, $filters, $limit, $offset);
        $total = $this->sportBlocks->countByType($type, $filters);

        return response()->json([
            'status' => 1,
            'html' => $this->renderSportBlocks($items, $routePrefix, $type),
            'count' => $items->count(),
            'has_more' => $total > $offset + $items->count(),
        ]);
    }

    private function getEventsList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false], 401);
        }

        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $userId = max((int)$request->input('user_id', $request->input('member_id', $viewer->id)), 1);
        $feed = (string)$request->input('feed', 'mygroups');
        $filters = $this->eventFilters($request);

        if ($feed === 'invited') {
            $events = $this->events->invitedEvents($userId, $limit, $offset, $filters);
            $total = $this->events->invitedEventsCount($userId, $filters);
        } else {
            $events = $this->events->myEvents($userId, $limit, $offset, $filters);
            $total = $this->events->myEventsCount($userId, $filters);
        }

        return response()->json([
            'status' => 1,
            'html' => $this->renderEvents($events),
            'count' => $events->count(),
            'has_more' => $total > $offset + $events->count(),
        ]);
    }

    private function getPopEventsList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $filters = $this->eventFilters($request);
        $events = $this->events->popularEvents($limit, $offset, $filters, $viewer);
        $total = $this->events->popularEventsCount($filters);

        return response()->json([
            'status' => 1,
            'html' => $this->renderEvents($events),
            'count' => $events->count(),
            'has_more' => $total > $offset + $events->count(),
        ]);
    }

    private function getPossibleFriends(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int)$request->input('number', 6), 1), 24);
        $users = $this->friends->possibleFriendsFor($viewer->id, $limit);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function getFriendsList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int)$request->input('number', 10), 1), 24);
        $offset = max((int)$request->input('offset', 0), 0);
        $userId = (int)$request->input('user_id', $viewer->id);
        $users = $this->friends->friendsFor($userId, $limit, $offset);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function addAsFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int)$request->input('user_id');

        if (!$viewer || $friendId < 1 || !$this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->requestFriendship($viewer->id, $friendId),
        ]);
    }

    private function acceptFriendship(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int)$request->input('user_id');

        if (!$viewer || $friendId < 1 || !$this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->acceptFriendship($viewer->id, $friendId),
        ]);
    }

    private function removeFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int)$request->input('user_id');

        if (!$viewer || $friendId < 1) {
            return response()->json(['status' => null, 'result' => ''], 422);
        }

        $removed = $this->friends->removeFriendship($viewer->id, $friendId);

        return response()->json([
            'status' => $removed ? 'success' : null,
            'result' => $removed ? 'success' : '',
        ]);
    }

    private function blockUser(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int)$request->input('user_id');

        if (!$viewer || $friendId < 1 || !$this->users->findActive($friendId)) {
            return response()->json(['status' => null, 'result' => ''], 422);
        }

        $blocked = $this->friends->blockUser($viewer->id, $friendId);

        return response()->json([
            'status' => $blocked ? 'success' : null,
            'result' => $blocked ? 'success' : '',
        ]);
    }

    private function unblockUser(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int)$request->input('user_id');

        if (!$viewer || $friendId < 1) {
            return response()->json(['status' => null, 'result' => ''], 422);
        }

        $unblocked = $this->friends->unblockUser($viewer->id, $friendId);

        return response()->json([
            'status' => $unblocked ? 'success' : null,
            'result' => $unblocked ? 'success' : '',
        ]);
    }

    private function changeCommunityMemberStatus(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $communityId = (int)$request->input('id', $request->input('community_id', 0));
        $status = (int)$request->input('status');
        $community = $communityId > 0
            ? ($this->communities->findTeam($communityId) ?: $this->communities->findGroup($communityId))
            : null;

        if (!$viewer || !$community || !in_array($status, [0, 1], true)) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->changeMembership($community, $viewer, $status);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
            'member' => $changed ? $this->communities->membershipType($community, $viewer) : null,
        ]);
    }

    private function sendCommunityInvitation(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $communityId = (int)$request->input('community_id', $request->input('id', 0));
        $community = $communityId > 0
            ? ($this->communities->findTeam($communityId) ?: $this->communities->findGroup($communityId))
            : null;

        if (!$viewer || !$community || !$this->communities->canInvite($community, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'count' => 0], 422);
        }

        $count = $this->communities->inviteFriends($community, $viewer);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'count' => $count,
        ]);
    }

    private function searchEvent(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $communityId = (int)$request->input('member_id', $request->input('community_id', 0));
        $type = (string)$request->input('eventable_type', 'team');
        $community = match ($type) {
            'group' => $communityId > 0 ? $this->communities->findGroup($communityId) : null,
            'team' => $communityId > 0 ? $this->communities->findTeam($communityId) : null,
            default => null,
        };

        if (!$viewer || !$community || !$this->communities->canManage($community, $viewer)) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0], 403);
        }

        $limit = min(max((int)$request->input('number', 10), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $search = trim((string)$request->input('search', ''));
        $filters = [
            'place' => trim((string)$request->input('place', '')),
            'sport' => trim((string)$request->input('sport', '')),
        ];
        $events = $type === 'team'
            ? $this->communities->searchEventsForTeam($community->id, $search, $limit, $offset, $filters)
            : $this->communities->searchEventsForCommunity($community->id, $type, $search, $limit, $offset, $filters);

        return response()->json([
            'status' => 1,
            'html' => view('front.teams._event-search-results', [
                'team' => $community,
                'events' => $events,
            ])->render(),
            'count' => $events->count(),
        ]);
    }

    private function changeEventCommunityStatus(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $communityId = (int)$request->input('community_id', $request->input('member_id', 0));
        $eventId = (int)$request->input('event_id', $request->input('id', 0));
        $status = (int)$request->input('status', 1);
        $type = (string)$request->input('eventable_type', '');
        $community = match ($type) {
            'group' => $communityId > 0 ? $this->communities->findGroup($communityId) : null,
            'team' => $communityId > 0 ? $this->communities->findTeam($communityId) : null,
            default => $communityId > 0 ? ($this->communities->findTeam($communityId) ?: $this->communities->findGroup($communityId)) : null,
        };

        if (!$viewer || !$community || $eventId < 1 || !$this->communities->canManage($community, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->changeEventMembership($community, $eventId, $status);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ]);
    }

    private function changeEventMemberStatus(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $eventId = (int)$request->input('event_id', $request->input('id', 0));
        $status = (int)$request->input('status');
        $event = $eventId > 0 ? $this->events->findActive($eventId) : null;

        if (!$viewer || !$event || !in_array($status, [0, 1], true)) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->events->changeMembership($event, $viewer, $status);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
            'member' => $changed ? $this->events->membershipType($event, $viewer) : null,
        ]);
    }

    private function sendEventInvitation(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $eventId = (int)$request->input('event_id', $request->input('id', 0));
        $event = $eventId > 0 ? $this->events->findActive($eventId) : null;

        if (!$viewer || !$event || !$this->events->canInvite($event, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'count' => 0], 422);
        }

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'count' => $this->events->inviteFriends($event, $viewer),
        ]);
    }

    private function getComments(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $type = (string)$request->input('commentable_type', 'user');
        $profileId = (int)$request->input('id', $request->input('content_id', 0));
        $limit = min(max((int)$request->input('number', 10), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);

        if (!in_array($type, ['user', 'photo', 'video', 'team', 'group', 'event'], true) || $profileId < 1) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false]);
        }

        $comments = $this->profiles->comments($type, $profileId, $limit, $offset, $viewer);

        return response()->json([
            'status' => 1,
            'html' => view('front.profile._comments', [
                'comments' => $comments,
                'viewer' => $viewer,
            ])->render(),
            'count' => $comments->count(),
            'has_more' => $this->profiles->hasMoreComments($type, $profileId, $limit, $offset),
        ]);
    }

    private function getPhotoInfo(Request $request): JsonResponse
    {
        $photoId = (int)$request->input('photo_id', $request->input('id', 0));

        if ($photoId < 1) {
            return response()->json(['status' => 0]);
        }

        /** @var Photo|null $photo */
        $photo = Photo::query()
            ->with(['owner', 'album'])
            ->whereKey($photoId)
            ->where('banned', false)
            ->first();

        if (!$photo) {
            return response()->json(['status' => 0]);
        }

        $photoUrl = FrontAssets::photoGallery($photo, 'photo') ?: FrontAssets::photoGallery($photo);

        if (!$photoUrl) {
            return response()->json(['status' => 0]);
        }

        $owner = $photo->owner;

        return response()->json([
            'status' => 1,
            'owner_id' => (int)($owner?->id ?? $photo->owner_id),
            'firstname' => (string)($owner?->firstname ?? ''),
            'lastname' => (string)($owner?->lastname ?? ''),
            'created' => $photo->created_at?->format('d.m.Y H:i') ?? '',
            'description' => (string)$photo->description,
            'photo' => $photoUrl,
            'liked' => Like::query()
                ->where('likeable_type', 'photo')
                ->where('content_id', $photo->id)
                ->count(),
            'tell' => Share::query()
                ->where('shareable_type', 'photo')
                ->where('content_id', $photo->id)
            ->count(),
        ]);
    }

    private function addPhotoAjax(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['info' => null, 'error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,gif', 'max:32768'],
            'categorie' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $albumId = (int)$request->input('categorie');
        $albumType = (string)$request->input('photoalbumable_type', 'user');
        $managedOwnerTypes = ['team', 'group', 'event'];
        $sportBlockTypes = ['playground', 'shop', 'fitness'];
        $album = match (true) {
            in_array($albumType, $managedOwnerTypes, true) => $this->photoalbums->album($albumId, $managedOwnerTypes),
            in_array($albumType, $sportBlockTypes, true) => $this->photoalbums->album($albumId, $sportBlockTypes),
            default => $this->photoalbums->album($albumId),
        };

        if (!$album) {
            return response()->json(['info' => null, 'error' => 'Нет доступа к альбому'], 403);
        }

        $canUpload = match (true) {
            $album->photoalbumable_type === 'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            $album->photoalbumable_type === 'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            $album->photoalbumable_type === 'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            in_array($album->photoalbumable_type, $sportBlockTypes, true) => $this->sportBlocks->isOwner(
                $this->sportBlocks->findByType((int)$album->owner_id, $album->photoalbumable_type),
                $viewer,
            ),
            default => $this->photoalbums->isOwner($album, $viewer),
        };

        if (!$canUpload) {
            return response()->json(['info' => null, 'error' => 'Нет доступа к альбому'], 403);
        }

        try {
            if (in_array($album->photoalbumable_type, array_merge($managedOwnerTypes, $sportBlockTypes), true)) {
                $photo = $this->photoalbums->storePhotoForAlbum(
                    $viewer,
                    $album,
                    $request->file('file'),
                    trim((string)$request->input('description', '')),
                );
            } else {
                $photo = $this->photoalbums->storePhoto(
                    $viewer,
                    $album,
                    $request->file('file'),
                    trim((string)$request->input('description', '')),
                );
            }
        } catch (\RuntimeException $exception) {
            return response()->json(['info' => null, 'error' => $exception->getMessage()], 422);
        }

        return response()->json([
            'info' => 'FILE_SUCCESSFULLY_DOWNLOADED',
            'id' => (int)$photo->id,
            'error' => null,
        ]);
    }

    private function getPhotosList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 6), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $ownerId = (int)$request->input('owner_id');
        $type = (string)$request->input('type', 'user');

        if ($ownerId < 1 || !in_array($type, ['user', 'team', 'group', 'event'], true)) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $photos = $type === 'user'
            ? $this->photoalbums->photosForUser($ownerId, $limit, $offset)
            : $this->photoalbums->photosForOwner($ownerId, $type, $limit, $offset);
        $canManage = match ($type) {
            'team' => $this->communities->canManage($this->communities->findTeam($ownerId), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup($ownerId), $viewer),
            'event' => $this->events->canManage($this->events->findActive($ownerId), $viewer),
            default => false,
        };

        return response()->json([
            'status' => $photos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderPhotos($photos, $viewer, $canManage),
            'has_more' => $type === 'user'
                ? $this->photoalbums->hasMoreUserPhotos($ownerId, $limit, $offset)
                : $this->photoalbums->hasMoreOwnerPhotos($ownerId, $type, $limit, $offset),
        ]);
    }

    private function getAlbumPhotos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 9), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->photoalbums->album((int)$request->input('id_album'), ['user', 'user_attach', 'team', 'group', 'event']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $photos = $this->photoalbums->albumPhotos($album, $limit, $offset);
        $canManage = match ($album->photoalbumable_type) {
            'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            default => $this->photoalbums->isOwner($album, $viewer),
        };

        return response()->json([
            'status' => $photos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderPhotos($photos, $viewer, $canManage),
            'has_more' => $this->photoalbums->hasMoreAlbumPhotos($album, $limit, $offset),
        ]);
    }

    private function removePic(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $photoId = (int)$request->input('id');

        if (!$viewer || $photoId < 1) {
            return response()->json(['result' => 'error'], 422);
        }

        $photo = $this->photoalbums->photo($photoId, ['user', 'user_attach', 'team', 'group', 'event']);

        if ($photo && in_array($photo->album?->photoalbumable_type, ['team', 'group', 'event'], true)) {
            $canManage = match ($photo->album->photoalbumable_type) {
                'team' => $this->communities->canManage($this->communities->findTeam((int)$photo->album->owner_id), $viewer),
                'group' => $this->communities->canManage($this->communities->findGroup((int)$photo->album->owner_id), $viewer),
                'event' => $this->events->canManage($this->events->findActive((int)$photo->album->owner_id), $viewer),
                default => false,
            };

            return response()->json([
                'result' => $canManage && $this->photoalbums->deletePhoto($photo)
                    ? 'success'
                    : 'error',
            ]);
        }

        return response()->json([
            'result' => $this->photoalbums->deletePhotoFor($viewer, $photoId) ? 'success' : 'error',
        ]);
    }

    private function getVideoInfo(Request $request): JsonResponse
    {
        $videoId = (int)$request->input('video_id', $request->input('id', 0));

        if ($videoId < 1) {
            return response()->json(['status' => 0]);
        }

        /** @var Video|null $video */
        $video = Video::query()
            ->with(['owner', 'album'])
            ->whereKey($videoId)
            ->where('banned', false)
            ->first();

        if (!$video || !$video->album || !in_array($video->album->videoalbumable_type, ['user', 'team', 'group', 'event'], true)) {
            return response()->json(['status' => 0]);
        }

        $viewer = $this->viewer();

        if ($viewer) {
            VideoView::query()->create([
                'user_id' => $viewer->id,
                'video_id' => $video->id,
                'time' => now(),
            ]);
        }

        $owner = $video->owner;

        return response()->json([
            'status' => 1,
            'owner_id' => (int)($owner?->id ?? $video->owner_id),
            'firstname' => (string)($owner?->firstname ?? ''),
            'lastname' => (string)($owner?->lastname ?? ''),
            'created' => $video->created_at?->format('d.m.Y H:i') ?? '',
            'description' => (string)$video->description,
            'thumb' => $this->videoalbums->thumbUrl((string)$video->provider, (string)$video->video),
            'video' => $this->videoalbums->playerHtml((string)$video->provider, (string)$video->video),
            'liked' => Like::query()
                ->where('likeable_type', 'video')
                ->where('content_id', $video->id)
                ->count(),
            'tell' => Share::query()
                ->where('shareable_type', 'video')
                ->where('content_id', $video->id)
                ->count(),
            'views' => VideoView::query()
                ->where('video_id', $video->id)
                ->count(),
        ]);
    }

    private function getVideosList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 6), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $ownerId = (int)$request->input('owner_id');
        $type = (string)$request->input('type', 'user');

        if ($ownerId < 1 || !in_array($type, ['user', 'team', 'group', 'event'], true)) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $videos = $type === 'user'
            ? $this->videoalbums->videosForUser($ownerId, $limit, $offset)
            : $this->videoalbums->videosForOwner($ownerId, $type, $limit, $offset);
        $canManage = match ($type) {
            'team' => $this->communities->canManage($this->communities->findTeam($ownerId), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup($ownerId), $viewer),
            'event' => $this->events->canManage($this->events->findActive($ownerId), $viewer),
            default => false,
        };

        return response()->json([
            'status' => $videos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderVideos($videos, $viewer, $canManage),
            'has_more' => $type === 'user'
                ? $this->videoalbums->hasMoreUserVideos($ownerId, $limit, $offset)
                : $this->videoalbums->hasMoreOwnerVideos($ownerId, $type, $limit, $offset),
        ]);
    }

    private function getAlbumVideos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 6), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->videoalbums->album((int)$request->input('id_album'), ['user', 'team', 'group', 'event']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $videos = $this->videoalbums->albumVideos($album, $limit, $offset);
        $canManage = match ($album->videoalbumable_type) {
            'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            default => $this->videoalbums->isOwner($album, $viewer),
        };

        return response()->json([
            'status' => $videos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderVideos($videos, $viewer, $canManage),
            'has_more' => $this->videoalbums->hasMoreAlbumVideos($album, $limit, $offset),
        ]);
    }

    private function removeVideo(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $videoId = (int)$request->input('id');

        if (!$viewer || $videoId < 1) {
            return response()->json(['result' => 'error'], 422);
        }

        /** @var Video|null $video */
        $video = Video::query()->with('album')->whereKey($videoId)->first();

        if ($video && in_array($video->album?->videoalbumable_type, ['team', 'group', 'event'], true)) {
            $canManage = match ($video->album->videoalbumable_type) {
                'team' => $this->communities->canManage($this->communities->findTeam((int)$video->album->owner_id), $viewer),
                'group' => $this->communities->canManage($this->communities->findGroup((int)$video->album->owner_id), $viewer),
                'event' => $this->events->canManage($this->events->findActive((int)$video->album->owner_id), $viewer),
                default => false,
            };

            return response()->json([
                'result' => $canManage && $this->videoalbums->deleteVideo($video)
                    ? 'success'
                    : 'error',
            ]);
        }

        return response()->json([
            'result' => $this->videoalbums->deleteVideoFor($viewer, $videoId) ? 'success' : 'error',
        ]);
    }

    private function addPhotoAjaxAttach(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 0, 'error' => 'Invalid file'], 422);
        }

        $album = Photoalbum::query()->firstOrCreate([
            'owner_id' => $viewer->id,
            'photoalbumable_type' => 'user_attach',
        ], [
            'name' => 'Мои прикрепленные фотографии',
        ]);

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(Str::random(32)) . '.' . $extension;
        $smallFilename = 's_' . $filename;
        $directory = 'images/photogallery/user_attach';
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            return response()->json(['status' => 0, 'error' => 'Invalid file'], 422);
        }

        $disk = Storage::disk('public');
        $originalPath = $directory . '/' . $filename;
        $smallPath = $directory . '/' . $smallFilename;

        if (!$disk->put($originalPath, $contents) || !$disk->put($smallPath, $contents)) {
            $disk->delete([$originalPath, $smallPath]);

            return response()->json(['status' => 0, 'error' => 'File was not saved'], 500);
        }

        /** @var Photo $photo */
        $photo = Photo::query()->create([
            'photoalbum_id' => $album->id,
            'small_photo' => $smallFilename,
            'photo' => $filename,
            'description' => '',
            'owner_id' => $viewer->id,
            'banned' => false,
            'moderate' => false,
        ])->load('album');

        return response()->json([
            'status' => 1,
            'num' => (int)$request->input('num', 0),
            'message' => [
                'id' => (int)$photo->id,
                'small_photo' => FrontAssets::photoGallery($photo),
                'photo' => FrontAssets::photoGallery($photo, 'photo'),
            ],
        ]);
    }

    private function uploadAvatar(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['result' => 'error', 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'x' => ['required', 'numeric', 'min:0'],
            'y' => ['required', 'numeric', 'min:0'],
            'w' => ['required', 'numeric', 'min:100'],
            'h' => ['required', 'numeric', 'min:100'],
        ]);

        try {
            $avatar = $this->profiles->cropTemporaryAvatar(
                $viewer,
                $request->file('avatar'),
                $validated,
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'result' => 'error',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'result' => 'success',
            'file' => $avatar['file'],
            'url' => $avatar['url'],
        ]);
    }

    private function uploadCover(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['result' => 'error', 'error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'x' => ['required', 'numeric', 'min:0'],
            'y' => ['required', 'numeric', 'min:0'],
            'w' => ['required', 'numeric', 'min:300'],
            'h' => ['required', 'numeric', 'min:80'],
        ]);

        try {
            $cover = $this->profiles->cropTemporaryCover(
                $viewer,
                $request->file('cover'),
                $validated,
            );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'result' => 'error',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'result' => 'success',
            'file' => $cover['file'],
            'url' => $cover['url'],
        ]);
    }

    private function addComment(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $type = (string)$request->input('commentable_type', 'user');
        $profileId = (int)$request->input('content_id');
        $comment = trim((string)$request->input('comment', ''));
        $attach = $request->input('attach', []);

        if (!$viewer || !in_array($type, ['user', 'photo', 'video', 'team', 'group', 'event'], true) || $profileId < 1 || ($comment === '' && empty($attach))) {
            return response()->json([
                'status' => false,
                'errors' => ['comment' => 'Заполните комментарий'],
            ], 422);
        }

        $behalfableType = '';
        $behalfId = 0;

        if (in_array($type, ['team', 'group'], true)) {
            $community = $type === 'team'
                ? $this->communities->findTeam($profileId)
                : $this->communities->findGroup($profileId);

            if (!$community || !$this->communities->permissions($community, $viewer)['wall']) {
                return response()->json([
                    'status' => false,
                    'errors' => ['comment' => $type === 'team' ? 'Нет доступа к ленте команды' : 'Нет доступа к ленте группы'],
                ], 403);
            }

            if ($request->boolean('author_community') && $this->communities->canManage($community, $viewer)) {
                $behalfableType = $type;
                $behalfId = $community->id;
            }
        }

        if ($type === 'event') {
            $event = $this->events->findActive($profileId);

            if (!$event || !$this->events->permissions($event, $viewer)['wall']) {
                return response()->json([
                    'status' => false,
                    'errors' => ['comment' => 'Нет доступа к ленте мероприятия'],
                ], 403);
            }
        }

        $created = $this->profiles->createWallComment($viewer, [
            'commentable_type' => $type,
            'content_id' => $profileId,
            'behalfable_type' => $behalfableType,
            'behalf_id' => $behalfId,
            'comment' => $comment,
            'parent_id' => $request->input('parent_id', 0),
            'attach' => $attach,
        ]);

        return response()->json([
            'status' => true,
            'id' => $created->id,
            'html' => view('front.profile._comment', [
                'comment' => $this->profiles->serializeComment($created, $viewer),
                'viewer' => $viewer,
            ])->render(),
        ]);
    }

    private function removeComment(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $commentId = (int)$request->input('id_comment', $request->input('id', 0));

        if (!$viewer || $commentId < 1) {
            return response()->json(['result' => ''], 422);
        }

        if (!$this->profiles->deleteComment($viewer, $commentId)) {
            return response()->json(['result' => ''], 403);
        }

        return response()->json(['result' => 'success']);
    }

    private function addMessage(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $receiverId = (int)$request->input('receiver_id');
        $receiver = $receiverId > 0 ? $this->users->findActive($receiverId) : null;
        $message = trim((string)$request->input('message', ''));
        $attach = $request->input('attach', []);

        if (!$viewer || !$receiver) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'Сообщение не было отправлено']], 422);
        }

        if ($message === '' && $this->attachmentIds($attach) === []) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'Введите сообщение']], 422);
        }

        if (!$this->messages->canSendMessage($viewer, $receiver)) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'Вы не можете написать сообщение пользователю']], 403);
        }

        $created = $this->messages->createMessage($viewer, $receiver, $message, $attach);

        return response()->json($this->messages->serializeMessage($created) + [
            'status' => 1,
            'count' => $this->messages->unreadCount($viewer),
        ]);
    }

    private function getMessages(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $receiverId = (int)$request->input('receiver_id');
        $receiver = $receiverId > 0 ? $this->users->findActive($receiverId) : null;

        if (!$viewer || !$receiver) {
            return response()->json(['item' => [], 'has_more' => false], 422);
        }

        $limit = min(max((int)$request->input('number', 10), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);

        return response()->json([
            'item' => $this->messages->conversation($viewer, $receiver, $limit, $offset)->values(),
            'has_more' => $this->messages->hasMoreConversation($viewer, $receiver, $limit, $offset),
            'count' => $this->messages->unreadCount($viewer),
        ]);
    }

    private function getNewMessages(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['item' => [], 'count' => 0], 401);
        }

        $receiverId = (int)$request->input('receiver_id');
        $receiver = $receiverId > 0 ? $this->users->findActive($receiverId) : null;
        $lastId = max((int)$request->input('last_id', 0), 0);

        return response()->json([
            'item' => $this->messages->newMessages($viewer, $lastId, $receiver)->values(),
            'count' => $this->messages->unreadCount($viewer),
        ]);
    }

    private function removeMessage(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $messageId = (int)$request->input('id', $request->input('message_id', 0));

        if (!$viewer || $messageId < 1) {
            return response()->json(['result' => 'error'], 422);
        }

        return response()->json([
            'result' => $this->messages->deleteMessageFor($viewer, $messageId) ? 'success' : 'error',
        ]);
    }

    private function removeDialog(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $partnerId = (int)$request->input('user_id', $request->input('receiver_id', 0));
        $partner = $partnerId > 0 ? $this->users->findActive($partnerId) : null;

        if (!$viewer || !$partner) {
            return response()->json(['result' => 'error'], 422);
        }

        return response()->json([
            'result' => $this->messages->deleteDialogFor($viewer, $partner) ? 'success' : 'error',
        ]);
    }

    private function liked(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $contentId = (int)$request->input('id');
        $type = (string)$request->input('likeable_type', 'comment');

        if (!$viewer || $contentId < 1 || $type === '') {
            return response()->json(['result' => ''], 422);
        }

        $query = Like::query()
            ->where('user_id', $viewer->id)
            ->where('content_id', $contentId)
            ->where('likeable_type', $type);

        if ($query->exists()) {
            $query->delete();
        } else {
            Like::query()->create([
                'user_id' => $viewer->id,
                'content_id' => $contentId,
                'likeable_type' => $type,
                'time' => now(),
            ]);
        }

        return response()->json([
            'result' => Like::query()
                ->where('content_id', $contentId)
                ->where('likeable_type', $type)
                ->count(),
        ]);
    }

    private function shared(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $contentId = (int)$request->input('id');
        $type = (string)$request->input('shareable_type', 'comment');

        if (!$viewer || $contentId < 1 || $type === '') {
            return response()->json(['result' => ''], 422);
        }

        Share::query()->firstOrCreate([
            'user_id' => $viewer->id,
            'content_id' => $contentId,
            'shareable_type' => $type,
        ], [
            'time' => now(),
        ]);

        return response()->json([
            'result' => Share::query()
                ->where('content_id', $contentId)
                ->where('shareable_type', $type)
                ->count(),
        ]);
    }

    private function searchCity(Request $request): JsonResponse
    {
        $city = trim((string)$request->query('city', ''));

        if ($city === '') {
            return response()->json(['item' => []]);
        }

        $items = GeoCity::query()
            ->where(function ($query) use ($city): void {
                $query
                    ->where('name_ru', 'like', '%' . $city . '%')
                    ->orWhere('name_en', 'like', '%' . $city . '%');
            })
            ->orderBy('sort')
            ->orderBy('name_ru')
            ->limit(10)
            ->get(['id', 'name_ru'])
            ->map(fn(GeoCity $city): array => [
                'id' => $city->id,
                'name' => $city->name_ru,
            ]);

        return response()->json(['item' => $items]);
    }

    private function searchSportTypes(Request $request): JsonResponse
    {
        $sportTypes = trim((string)$request->query('sport_types', ''));

        if ($sportTypes === '') {
            return response()->json(['item' => []]);
        }

        $items = SportType::query()
            ->where('name', 'like', '%' . $sportTypes . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn(SportType $sportType): array => [
                'id' => $sportType->id,
                'name' => $sportType->name,
            ]);

        return response()->json(['item' => $items]);
    }

    private function viewer(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return $user;
    }

    private function attachmentIds(mixed $attach): array
    {
        if (is_string($attach)) {
            $attach = explode(',', $attach);
        }

        if (!is_array($attach)) {
            return [];
        }

        return collect($attach)
            ->flatMap(fn($value): array => is_array($value) ? $value : [$value])
            ->map(fn($value): int => (int)$value)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function renderPhotos($photos, ?User $viewer, bool $canManage = false): string
    {
        return $photos
            ->map(fn(array $photo): string => view('front.photoalbums._photo-card', [
                'photo' => $photo,
                'viewer' => $viewer,
                'canManage' => $canManage,
            ])->render())
            ->implode('');
    }

    private function renderVideos($videos, ?User $viewer, bool $canManage = false): string
    {
        return $videos
            ->map(fn(array $video): string => view('front.videoalbums._video-card', [
                'video' => $video,
                'viewer' => $viewer,
                'canManage' => $canManage,
            ])->render())
            ->implode('');
    }

    private function communitiesForViewer(Collection $items, User $viewer): Collection
    {
        return $items->map(function (array $item) use ($viewer): array {
            $role = $this->communities->role((int)$item['id'], $viewer->id);

            $item['status'] = $this->communities->roleLabel($role);
            $item['can_edit'] = $role === 1;

            return $item;
        });
    }

    private function renderCommunities(Collection $items, string $type): string
    {
        $view = $type === 'team' ? 'front.teams._team-card' : 'front.groups._group-card';
        $key = $type === 'team' ? 'team' : 'group';

        return $items
            ->map(fn(array $item): string => view($view, [$key => $item])->render())
            ->implode('');
    }

    private function renderEvents(Collection $items): string
    {
        return $items
            ->map(fn(array $event): string => view('front.events._event-card', ['event' => $event])->render())
            ->implode('');
    }

    private function renderSportBlocks(Collection $items, string $routePrefix, string $type): string
    {
        return view('front.sport-blocks._cards', [
            'items' => $items,
            'routePrefix' => $routePrefix,
            'viewer' => $this->viewer(),
            'editLabel' => $type === 'playground' ? 'редактирование площадки' : 'Редактировать',
        ])->render();
    }

    private function communityFilters(Request $request): array
    {
        return [
            'place' => trim((string)$request->input('place', '')),
            'sport' => trim((string)$request->input('sport', '')),
            'search' => trim((string)$request->input('search', '')),
            'id_place' => (int)$request->input('id_place', 0),
            'id_sport' => (int)$request->input('id_sport', 0),
        ];
    }

    private function eventFilters(Request $request): array
    {
        $date = (string) $request->input('date', '');
        $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));

        return [
            'place' => trim((string)$request->input('place', '')),
            'sport' => trim((string)$request->input('sport', '')),
            'search' => trim((string)$request->input('search', '')),
            'date' => $validDate ? $date : '',
            'id_place' => (int)$request->input('id_place', 0),
            'id_sport' => (int)$request->input('id_sport', 0),
        ];
    }

    private function sportBlockFilters(Request $request): array
    {
        return [
            'place' => trim((string) $request->input('place', '')),
            'search' => trim((string) $request->input('search', '')),
            'id_place' => (int) $request->input('id_place', 0),
        ];
    }
}
