<?php

namespace App\Http\Controllers\Front;

use App\DTO\Message\MessageData;
use App\DTO\Photo\PhotoUploadData;
use App\DTO\Profile\CommentData;
use App\DTO\Profile\ImageCropData;
use App\Helpers\FrontAssets;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Ajax\AddCommentRequest;
use App\Http\Requests\Front\Ajax\AddMessageRequest;
use App\Http\Requests\Front\Ajax\AttachmentPhotoRequest;
use App\Http\Requests\Front\Ajax\CropAvatarRequest;
use App\Http\Requests\Front\Ajax\CropCoverRequest;
use App\Http\Requests\Front\Ajax\PhotoUploadRequest;
use App\Models\AcceptedEventMember;
use App\Models\Comment;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\Friend;
use App\Models\GeoCity;
use App\Models\Like;
use App\Models\Photo;
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
use App\Service\CommunityInvitationNotificationService;
use App\Service\EventInvitationNotificationService;
use App\Service\ProfileCoverCropService;
use App\Service\VideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AjaxController extends Controller
{
    /**
     * Connects repositories needed by AJAX front handlers.
     */
    public function __construct(
        private readonly NewsRepository       $news,
        private readonly FriendRepository     $friends,
        private readonly UserRepository       $users,
        private readonly ProfileRepository    $profiles,
        private readonly MessageRepository    $messages,
        private readonly PhotoalbumRepository $photoAlbums,
        private readonly VideoalbumRepository $videoAlbums,
        private readonly CommunityRepository  $communities,
        private readonly EventRepository      $events,
        private readonly SportBlockRepository $sportBlocks,
        private readonly ProfileCoverCropService $profileCovers,
        private readonly CommunityInvitationNotificationService $communityInvitations,
        private readonly EventInvitationNotificationService $eventInvitations,
        private readonly VideoService         $videos,
    )
    {
    }

    /**
     * Routes an AJAX request named actions and returns a JSON response.
     */
    public function handle(Request $request, string $action): JsonResponse
    {
        return match ($action) {
            'get_usernews_list' => $this->getUserNewsList($request),
            'get_communities_list' => $this->getCommunitiesList($request),
            'get_pop_communities_list' => $this->getPopCommunitiesList($request),
            'get_sport_blocks_list' => $this->getSportBlocksList($request),
            'get_events_list' => $this->getEventsList($request),
            'get_pop_events_list' => $this->getPopEventsList($request),
            'get_possible_friends' => $this->getPossibleFriends($request),
            'get_friends_list' => $this->getFriendsList($request),
            'add_as_friend' => $this->addAsFriend($request),
            'accept_friendship' => $this->acceptFriendship($request),
            'remove_friend' => $this->removeFriend($request),
            'block_user' => $this->blockUser($request),
            'unblock_user' => $this->unblockUser($request),
            'change_member_status' => $this->changeCommunityMemberStatus($request),
            'get_community_invite_friends' => $this->getCommunityInviteFriends($request),
            'send_community_invitation' => $this->sendCommunityInvitation($request),
            'remove_community_member' => $this->removeCommunityMember($request),
            'block_community_member' => $this->blockCommunityMember($request),
            'unblock_community_member' => $this->unblockCommunityMember($request),
            'search_community_admin_candidates' => $this->searchCommunityAdminCandidates($request),
            'add_community_admin' => $this->addCommunityAdmin($request),
            'remove_community_admin' => $this->removeCommunityAdmin($request),
            'search_event' => $this->searchEvent($request),
            'change_event_community_status' => $this->changeEventCommunityStatus($request),
            'change_event_memberstatus' => $this->changeEventMemberStatus($request),
            'get_event_invite_friends' => $this->getEventInviteFriends($request),
            'send_event_invitation' => $this->sendEventInvitation($request),
            'get_comments' => $this->getComments($request),
            'get_photoinfo' => $this->getPhotoInfo($request),
            'add_photo_ajax' => $this->addPhotoAjax($request),
            'add_photo_ajax_attach' => $this->addPhotoAjaxAttach($request),
            'get_photos_list' => $this->getPhotosList($request),
            'get_album_photos' => $this->getAlbumPhotos($request),
            'remove_pic' => $this->removePic($request),
            'get_video_info' => $this->getVideoInfo($request),
            'get_videos_list' => $this->getVideosList($request),
            'get_album_videos' => $this->getAlbumVideos($request),
            'remove_video' => $this->removeVideo($request),
            'upload_avatar' => $this->uploadAvatar($request),
            'upload_cover' => $this->uploadCover($request),
            'add_comment' => $this->addComment($request),
            'remove_comment' => $this->removeComment($request),
            'add_message' => $this->addMessage($request),
            'get_messages' => $this->getMessages($request),
            'get_new_messages' => $this->getNewMessages($request),
            'get_push_notifications' => $this->getPushNotifications($request),
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

    /**
     * Returns next user news page for infinite scrolling.
     */
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

    /**
     * Returns list of my or invited teams/groups with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            'html' => $this->renderCommunities($this->communitiesForViewer($items, $viewer), $type, $feed === 'invited'),
            'count' => $items->count(),
            'has_more' => $nextItems->isNotEmpty(),
        ]);
    }

    /**
     * Returns popular team or group with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            ? $this->communities->popularTeams($limit, $offset, $filters, $viewer)
            : $this->communities->popularGroups($limit, $offset, $filters, $viewer);
        $nextItems = $type === 'team'
            ? $this->communities->popularTeams(1, $offset + $limit, $filters, $viewer)
            : $this->communities->popularGroups(1, $offset + $limit, $filters, $viewer);

        return response()->json([
            'status' => 1,
            'html' => $this->renderCommunities($this->communitiesForViewer($items, $viewer), $type),
            'count' => $items->count(),
            'has_more' => $nextItems->isNotEmpty(),
        ]);
    }

    /**
     * Returns cards of sites, stores or fitness for AJAX loading.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getSportBlocksList(Request $request): JsonResponse
    {
        $limit = min(max((int)$request->input('number', 5), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);
        $type = (string)$request->input('type', 'playground');
        $routePrefix = match ($type) {
            'playground' => 'front.playgrounds',
            'shop' => 'front.shops',
            'fitness' => 'front.fitness',
            default => null,
        };

        if (!$routePrefix) {
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

    /**
     * Returns my or invited events for AJAX loading.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Returns popular events for AJAX loading.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Returns recommendations possible friends для current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Returns page of friends list selected user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Creates a friend request from the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Accepts incoming requests to friends.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Deletes a friend or rejects a request to friends.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Blocks selected user for the current profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Removes blocks from the selected user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Changes the user's participation status in teams or groups.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Sends a user an invitation to a team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        $userIds = collect((array)$request->input('user_ids', []))
            ->map(fn ($id): int => (int)$id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $invitees = $this->communities->inviteFriendUsers($community, $viewer, $userIds);
        $this->communityInvitations->sendInvitations($community, $viewer, $invitees);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'count' => $invitees->count(),
        ]);
    }

    /**
     * Returns list of friends who can be invited to team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getCommunityInviteFriends(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $communityId = (int)$request->input('community_id', $request->input('id', 0));
        $community = $communityId > 0
            ? ($this->communities->findTeam($communityId) ?: $this->communities->findGroup($communityId))
            : null;

        if (!$viewer || !$community || !$this->communities->canInvite($community, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'html' => '', 'count' => 0], 422);
        }

        $friends = $this->communities->invitableFriends($community, $viewer);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'html' => view('front.communities._invite-friends-list', ['friends' => $friends])->render(),
            'count' => $friends->count(),
        ]);
    }

    /**
     * Deletes members из team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function removeCommunityMember(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $userId = (int)$request->input('user_id');

        if (!$viewer || !$community || $userId < 1) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->removeMember($community, $viewer, $userId);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ], $changed ? 200 : 403);
    }

    /**
     * Blocks members team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function blockCommunityMember(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $userId = (int)$request->input('user_id');

        if (!$viewer || !$community || $userId < 1) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->blockMember($community, $viewer, $userId);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ], $changed ? 200 : 403);
    }

    /**
     * Deletes user from the team or group blacklist.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function unblockCommunityMember(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $userId = (int)$request->input('user_id');

        if (!$viewer || !$community || $userId < 1) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->removeBlockedMember($community, $viewer, $userId);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ], $changed ? 200 : 403);
    }

    /**
     * Searches for users who can be assigned as administrators.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function searchCommunityAdminCandidates(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $search = (string)$request->input('q', $request->input('search', ''));

        if (!$viewer || !$community || !$this->communities->isOwner($community, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'html' => '', 'count' => 0], 403);
        }

        $users = $this->communities->searchAdminCandidates($community, $viewer, $search);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'html' => view('front.communities._admin-candidates-list', ['users' => $users])->render(),
            'count' => $users->count(),
        ]);
    }

    /**
     * Assigns user administrator to team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function addCommunityAdmin(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $userId = (int)$request->input('user_id');

        if (!$viewer || !$community || $userId < 1) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->addAdmin($community, $viewer, $userId);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ], $changed ? 200 : 403);
    }

    /**
     * Filmed by administrator team or group.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function removeCommunityAdmin(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $community = $this->communityFromRequest($request);
        $userId = (int)$request->input('user_id');

        if (!$viewer || !$community || $userId < 1) {
            return response()->json(['status' => 0, 'result' => 'error'], 422);
        }

        $changed = $this->communities->removeAdmin($community, $viewer, $userId);

        return response()->json([
            'status' => $changed ? 1 : 0,
            'result' => $changed ? 'success' : 'error',
        ], $changed ? 200 : 403);
    }

    /**
     * Looks for events that can be associated with teams or groups.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Changes the status of a team or group's participation in an event.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Changes the user's participation status in the event.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Returns list of friends who can be invited to the event.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getEventInviteFriends(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $eventId = (int)$request->input('event_id', $request->input('id', 0));
        $event = $eventId > 0 ? $this->events->findActive($eventId) : null;

        if (!$viewer || !$event || !$this->events->canInvite($event, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'html' => '', 'count' => 0], 422);
        }

        $friends = $this->events->invitableFriends($event, $viewer);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'html' => view('front.communities._invite-friends-list', ['friends' => $friends])->render(),
            'count' => $friends->count(),
        ]);
    }

    /**
     * Sends an event invitation to the selected friends.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function sendEventInvitation(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $eventId = (int)$request->input('event_id', $request->input('id', 0));
        $event = $eventId > 0 ? $this->events->findActive($eventId) : null;

        if (!$viewer || !$event || !$this->events->canInvite($event, $viewer)) {
            return response()->json(['status' => 0, 'result' => 'error', 'count' => 0], 422);
        }

        $userIds = collect((array)$request->input('user_ids', []))
            ->map(fn ($id): int => (int)$id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $invitees = $this->events->inviteFriendUsers($event, $viewer, $userIds);
        $this->eventInvitations->sendInvitations($event, $viewer, $invitees);

        return response()->json([
            'status' => 1,
            'result' => 'success',
            'count' => $invitees->count(),
        ]);
    }

    /**
     * Returns page comments for a profile, event or entity.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        if (in_array($type, ['team', 'group'], true) && ! $this->canViewOwnerMedia($type, $profileId, 'wall', $viewer)) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false]);
        }

        if ($type === 'event' && ! $this->canViewOwnerMedia($type, $profileId, 'wall', $viewer)) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false]);
        }

        if ($type === 'user' && ! $this->canViewUserSection($profileId, 'wall', $viewer)) {
            return response()->json(['status' => 0, 'html' => '', 'count' => 0, 'has_more' => false]);
        }

        if (in_array($type, ['photo', 'video'], true) && ! $this->canViewMediaEntity($type, $profileId, $viewer)) {
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

    /**
     * Returns data photo and adjacent gallery elements.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
        $viewer = $this->viewer();

        if (
            $photo->album
            && ! $this->canViewOwnerMedia((string) $photo->album->photoalbumable_type, (int) $photo->album->owner_id, 'photo', $viewer)
        ) {
            return response()->json(['status' => 0]);
        }

        $likesQuery = Like::query()
            ->where('likeable_type', 'photo')
            ->where('content_id', $photo->id);
        $sharesQuery = Share::query()
            ->where('shareable_type', 'photo')
            ->where('content_id', $photo->id);

        return response()->json([
            'status' => 1,
            'owner_id' => (int)($owner?->id ?? $photo->owner_id),
            'firstname' => (string)($owner?->firstname ?? ''),
            'lastname' => (string)($owner?->lastname ?? ''),
            'created' => $photo->created_at?->format('d.m.Y H:i') ?? '',
            'description' => (string)$photo->description,
            'photo' => $photoUrl,
            'liked' => (clone $likesQuery)->count(),
            'tell' => (clone $sharesQuery)->count(),
            'liked_by_user' => $viewer ? (clone $likesQuery)->where('user_id', $viewer->id)->exists() : false,
            'shared_by_user' => $viewer ? (clone $sharesQuery)->where('user_id', $viewer->id)->exists() : false,
        ]);
    }

    /**
     * Loads a photo via AJAX into the selected album or entity.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function addPhotoAjax(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['info' => null, 'error' => 'Unauthorized'], 401);
        }

        $photoData = PhotoUploadData::fromArray($this->validateAjax($request, PhotoUploadRequest::class) + [
            'file' => $request->file('file'),
        ]);
        $albumId = $photoData->albumId;
        $albumType = (string)$request->input('photoalbumable_type', 'user');
        $managedOwnerTypes = ['team', 'group', 'event'];
        $sportBlockTypes = ['playground', 'shop', 'fitness'];
        $album = match (true) {
            in_array($albumType, $managedOwnerTypes, true) => $this->photoAlbums->album($albumId, $managedOwnerTypes),
            in_array($albumType, $sportBlockTypes, true) => $this->photoAlbums->album($albumId, $sportBlockTypes),
            default => $this->photoAlbums->album($albumId),
        };

        if (!$album) {
            return response()->json(['info' => null, 'error' => 'No access to the album'], 403);
        }

        $canUpload = match (true) {
            $album->photoalbumable_type === 'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            $album->photoalbumable_type === 'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            $album->photoalbumable_type === 'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            in_array($album->photoalbumable_type, $sportBlockTypes, true) => $this->sportBlocks->isOwner(
                $this->sportBlocks->findByType((int)$album->owner_id, $album->photoalbumable_type),
                $viewer,
            ),
            default => $this->photoAlbums->isOwner($album, $viewer),
        };

        if (!$canUpload) {
            return response()->json(['info' => null, 'error' => 'No access to the album'], 403);
        }

        try {
            if (in_array($album->photoalbumable_type, array_merge($managedOwnerTypes, $sportBlockTypes), true)) {
                $photo = $this->photoAlbums->storePhotoForAlbum(
                    $viewer,
                    $album,
                    $photoData,
                );
            } else {
                $photo = $this->photoAlbums->storePhoto(
                    $viewer,
                    $album,
                    $photoData,
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

    /**
     * Returns page photos for owner or current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        if (! $this->canViewOwnerMedia($type, $ownerId, 'photo', $viewer)) {
            return $this->emptyMediaResponse();
        }

        $photos = $type === 'user'
            ? $this->photoAlbums->photosForUser($ownerId, $limit, $offset)
            : $this->photoAlbums->photosForOwner($ownerId, $type, $limit, $offset);
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
                ? $this->photoAlbums->hasMoreUserPhotos($ownerId, $limit, $offset)
                : $this->photoAlbums->hasMoreOwnerPhotos($ownerId, $type, $limit, $offset),
        ]);
    }

    /**
     * Returns page photos of a specific album.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getAlbumPhotos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 9), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->photoAlbums->album((int)$request->input('id_album'), ['user', 'user_attach', 'team', 'group', 'event']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        if (! $this->canViewOwnerMedia((string) $album->photoalbumable_type, (int) $album->owner_id, 'photo', $viewer)) {
            return $this->emptyMediaResponse();
        }

        $photos = $this->photoAlbums->albumPhotos($album, $limit, $offset);
        $canManage = match ($album->photoalbumable_type) {
            'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            default => $this->photoAlbums->isOwner($album, $viewer),
        };

        return response()->json([
            'status' => $photos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderPhotos($photos, $viewer, $canManage),
            'has_more' => $this->photoAlbums->hasMoreAlbumPhotos($album, $limit, $offset),
        ]);
    }

    /**
     * Deletes photo after validation by current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function removePic(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $photoId = (int)$request->input('id');

        if (!$viewer || $photoId < 1) {
            return response()->json(['result' => 'error'], 422);
        }

        $photo = $this->photoAlbums->photo($photoId, ['user', 'user_attach', 'team', 'group', 'event']);

        if ($photo && in_array($photo->album?->photoalbumable_type, ['team', 'group', 'event'], true)) {
            $canManage = match ($photo->album->photoalbumable_type) {
                'team' => $this->communities->canManage($this->communities->findTeam((int)$photo->album->owner_id), $viewer),
                'group' => $this->communities->canManage($this->communities->findGroup((int)$photo->album->owner_id), $viewer),
                'event' => $this->events->canManage($this->events->findActive((int)$photo->album->owner_id), $viewer),
                default => false,
            };

            return response()->json([
                'result' => $canManage && $this->photoAlbums->deletePhoto($photo)
                    ? 'success'
                    : 'error',
            ]);
        }

        return response()->json([
            'result' => $this->photoAlbums->deletePhotoFor($viewer, $photoId) ? 'success' : 'error',
        ]);
    }

    /**
     * Returns data video and adjacent video album elements.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        if (! $this->canViewOwnerMedia((string) $video->album->videoalbumable_type, (int) $video->album->owner_id, 'video', $viewer)) {
            return response()->json(['status' => 0]);
        }

        if ($viewer) {
            VideoView::query()->create([
                'user_id' => $viewer->id,
                'video_id' => $video->id,
                'time' => now(),
            ]);
        }

        $owner = $video->owner;
        $likesQuery = Like::query()
            ->where('likeable_type', 'video')
            ->where('content_id', $video->id);
        $sharesQuery = Share::query()
            ->where('shareable_type', 'video')
            ->where('content_id', $video->id);

        return response()->json([
            'status' => 1,
            'owner_id' => (int)($owner?->id ?? $video->owner_id),
            'firstname' => (string)($owner?->firstname ?? ''),
            'lastname' => (string)($owner?->lastname ?? ''),
            'owner_avatar' => FrontAssets::userAvatar($owner),
            'created' => $video->created_at?->format('d.m.Y H:i') ?? '',
            'description' => (string)$video->description,
            'thumb' => StringHelper::thumbUrl((string)$video->provider, (string)$video->video),
            'video' => $this->videos->playerHtml((string)$video->provider, (string)$video->video),
            'liked' => (clone $likesQuery)->count(),
            'tell' => (clone $sharesQuery)->count(),
            'liked_by_user' => $viewer ? (clone $likesQuery)->where('user_id', $viewer->id)->exists() : false,
            'shared_by_user' => $viewer ? (clone $sharesQuery)->where('user_id', $viewer->id)->exists() : false,
            'views' => VideoView::query()
                ->where('video_id', $video->id)
                ->count(),
        ]);
    }

    /**
     * Returns page video for owner or current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

        if (! $this->canViewOwnerMedia($type, $ownerId, 'video', $viewer)) {
            return $this->emptyMediaResponse();
        }

        $videos = $type === 'user'
            ? $this->videoAlbums->videosForUser($ownerId, $limit, $offset)
            : $this->videoAlbums->videosForOwner($ownerId, $type, $limit, $offset);
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
                ? $this->videoAlbums->hasMoreUserVideos($ownerId, $limit, $offset)
                : $this->videoAlbums->hasMoreOwnerVideos($ownerId, $type, $limit, $offset),
        ]);
    }

    /**
     * Returns page video of a specific album.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getAlbumVideos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 6), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->videoAlbums->album((int)$request->input('id_album'), ['user', 'team', 'group', 'event']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        if (! $this->canViewOwnerMedia((string) $album->videoalbumable_type, (int) $album->owner_id, 'video', $viewer)) {
            return $this->emptyMediaResponse();
        }

        $videos = $this->videoAlbums->albumVideos($album, $limit, $offset);
        $canManage = match ($album->videoalbumable_type) {
            'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
            'group' => $this->communities->canManage($this->communities->findGroup((int)$album->owner_id), $viewer),
            'event' => $this->events->canManage($this->events->findActive((int)$album->owner_id), $viewer),
            default => $this->videoAlbums->isOwner($album, $viewer),
        };

        return response()->json([
            'status' => $videos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderVideos($videos, $viewer, $canManage),
            'has_more' => $this->videoAlbums->hasMoreAlbumVideos($album, $limit, $offset),
        ]);
    }

    /**
     * Deletes video after validation by current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
                'result' => $canManage && $this->videoAlbums->deleteVideo($video)
                    ? 'success'
                    : 'error',
            ]);
        }

        return response()->json([
            'result' => $this->videoAlbums->deleteVideoFor($viewer, $videoId) ? 'success' : 'error',
        ]);
    }

    /**
     * Loads a photo attachment for a comment or message.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function addPhotoAjaxAttach(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $photoData = PhotoUploadData::fromArray($this->validateAjax($request, AttachmentPhotoRequest::class) + [
            'file' => $request->file('file'),
        ]);

        try {
            $photo = $this->photoAlbums->storeAttachmentPhoto($viewer, $photoData);
        } catch (\RuntimeException $exception) {
            return response()->json(['status' => 0, 'error' => $exception->getMessage()], 422);
        }

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

    /**
     * Takes an avatar file, creates a temporary crop and returns a data preview.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function uploadAvatar(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['result' => 'error', 'error' => 'Unauthorized'], 401);
        }

        $cropData = ImageCropData::fromArray($this->validateAjax($request, CropAvatarRequest::class) + [
            'file' => $request->file('avatar'),
        ]);

        try {
            $avatar = $this->profiles->cropTemporaryAvatar(
                $viewer,
                $cropData,
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

    /**
     * Takes a cover file, creates a temporary crop and returns a data preview.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function uploadCover(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['result' => 'error', 'error' => 'Unauthorized'], 401);
        }

        $cropData = ImageCropData::fromArray($this->validateAjax($request, CropCoverRequest::class) + [
            'file' => $request->file('cover'),
        ]);

        try {
            $cover = $this->profileCovers->cropTemporaryCover(
                $viewer,
                $cropData,
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

    /**
     * Creates a comment on a profile, event or other supported entity.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function addComment(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $validated = $this->validateAjax($request, AddCommentRequest::class);
        $type = (string)($validated['commentable_type'] ?? 'user');
        $profileId = (int)($validated['content_id'] ?? 0);
        $comment = trim((string)($validated['comment'] ?? ''));
        $attach = $validated['attach'] ?? [];

        if (!$viewer || !in_array($type, ['user', 'photo', 'video', 'team', 'group', 'event'], true) || $profileId < 1 || ($comment === '' && empty($attach))) {
            return response()->json([
                'status' => false,
                'errors' => ['comment' => 'Enter a comment'],
            ], 422);
        }

        if (in_array($type, ['photo', 'video'], true) && ! $this->canViewMediaEntity($type, $profileId, $viewer)) {
            return response()->json([
                'status' => false,
                'errors' => ['comment' => 'No access to this section'],
            ], 403);
        }

        if ($type === 'user' && ! $this->canViewUserSection($profileId, 'wall', $viewer)) {
            return response()->json([
                'status' => false,
                'errors' => ['comment' => 'No access to the user wall'],
            ], 403);
        }

        $behalfableType = '';
        $behalfId = 0;

        if (in_array($type, ['team', 'group'], true)) {
            $community = $type === 'team'
                ? $this->communities->findTeam($profileId)
                : $this->communities->findGroup($profileId);

            if (!$this->communities->canViewSection($community, $viewer, 'wall')) {
                return response()->json([
                    'status' => false,
                    'errors' => ['comment' => $type === 'team' ? 'No access to the team feed' : 'No access to the group feed'],
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
                    'errors' => ['comment' => 'No access to the event feed'],
                ], 403);
            }
        }

        $commentData = CommentData::fromArray([
            'commentable_type' => $type,
            'content_id' => $profileId,
            'behalfable_type' => $behalfableType,
            'behalf_id' => $behalfId,
            'comment' => $comment,
            'parent_id' => $validated['parent_id'] ?? 0,
            'attach' => $attach,
        ]);
        $created = $this->profiles->createWallComment($viewer, $commentData);

        return response()->json([
            'status' => true,
            'id' => $created->id,
            'html' => view('front.profile._comment', [
                'comment' => $this->profiles->serializeComment($created, $viewer),
                'viewer' => $viewer,
            ])->render(),
        ]);
    }

    /**
     * Delete the comment after validating rights.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Creates a message in the current user dialog.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function addMessage(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $validated = $this->validateAjax($request, AddMessageRequest::class);
        $receiverId = (int)($validated['receiver_id'] ?? 0);
        $receiver = $receiverId > 0 ? $this->users->findActive($receiverId) : null;
        $messageData = MessageData::fromArray($validated);

        if (!$viewer || !$receiver) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'Message was not sent']], 422);
        }

        if ($messageData->content === '' && $this->attachmentIds($messageData->attach) === []) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'Enter message']], 422);
        }

        if (!$this->messages->canSendMessage($viewer, $receiver)) {
            return response()->json(['status' => 0, 'errors' => ['message' => 'You cannot message this user']], 403);
        }

        $created = $this->messages->createMessage($viewer, $receiver, $messageData);

        return response()->json($this->messages->serializeMessage($created) + [
                'status' => 1,
                'count' => $this->messages->unreadDialoguesCount($viewer),
            ]);
    }

    /**
     * Returns page of selected dialog messages.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            'count' => $this->messages->unreadDialoguesCount($viewer),
        ]);
    }

    /**
     * Returns new conversation messages after the specified ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
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
            'count' => $this->messages->unreadDialoguesCount($viewer),
        ]);
    }

    /**
     * Returns current events for push notifications current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function getPushNotifications(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (!$viewer) {
            return response()->json(['status' => 0, 'items' => []], 401);
        }

        $items = collect()
            ->merge($this->friendRequestPushNotifications($viewer))
            ->merge($this->communityInvitePushNotifications($viewer))
            ->merge($this->eventInvitePushNotifications($viewer))
            ->sortByDesc(fn (array $item): int => (int) ($item['sort'] ?? 0))
            ->map(function (array $item): array {
                unset($item['sort']);

                return $item;
            })
            ->values();

        return response()->json([
            'status' => 1,
            'items' => $items,
        ]);
    }

    /**
     * Prepares notifications about incoming requests to friends.
     *
     * @param User $viewer
     * @return Collection
     */
    private function friendRequestPushNotifications(User $viewer): Collection
    {
        return Friend::query()
            ->with(['user'])
            ->where('friend_id', $viewer->id)
            ->where('status', 0)
            ->orderByDesc('added')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (Friend $friend): array {
                $sender = $friend->user;
                $name = $this->pushUserName($sender);

                return [
                    'key' => 'friend_request:' . $friend->id,
                    'type' => 'friend_request',
                    'text' => $name . ' sent you a friend request',
                    'url' => route('front.friends.index'),
                    'sort' => $friend->added?->getTimestamp() ?? (int) $friend->id,
                ];
            });
    }

    /**
     * Prepares notifications of invitations to group and team.
     *
     * @param User $viewer
     * @return Collection
     */
    private function communityInvitePushNotifications(User $viewer): Collection
    {
        return CommunityRole::query()
            ->with(['community'])
            ->where('user_id', $viewer->id)
            ->where('role', 5)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (CommunityRole $role): ?array {
                $community = $role->community;

                if (!$community || !$community->isVisible()) {
                    return null;
                }

                $isGroup = $community->type === 'group';

                return [
                    'key' => 'community_invite:' . $role->id,
                    'type' => $isGroup ? 'group_invite' : 'team_invite',
                    'text' => 'You have been invited to ' . ($isGroup ? 'group ' : 'team ') . $community->name,
                    'url' => route($isGroup ? 'front.groups.show' : 'front.teams.show', ['community' => $community->id]),
                    'sort' => (int) $role->id,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Prepares notifications of invitations to events.
     *
     * @param User $viewer
     * @return Collection
     */
    private function eventInvitePushNotifications(User $viewer): Collection
    {
        return AcceptedEventMember::query()
            ->with(['event'])
            ->where('eventable_type', 'user')
            ->where('member_id', $viewer->id)
            ->where('role', 5)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (AcceptedEventMember $member): ?array {
                $event = $member->event;

                if (!$event || !$event->isVisible()) {
                    return null;
                }

                return [
                    'key' => 'event_invite:' . $member->id,
                    'type' => 'event_invite',
                    'text' => 'You have been invited to an event ' . $event->name,
                    'url' => route('front.events.show', ['event' => $event->id]),
                    'sort' => (int) $member->id,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Returns user's display name for the notification text.
     *
     * @param User|null $user
     * @return string
     */
    private function pushUserName(?User $user): string
    {
        return $user?->displayName() ?: 'User';
    }

    /**
     * Delete message current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Delete dialog for current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Toggles the like for the supported entity and returns the new amount.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function liked(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $contentId = (int)$request->input('id');
        $type = (string)$request->input('likeable_type', 'comment');

        if (!$viewer || $contentId < 1 || !in_array($type, ['photo', 'video', 'comment'], true)) {
            return response()->json(['result' => ''], 422);
        }

        if (in_array($type, ['photo', 'video'], true) && ! $this->canViewMediaEntity($type, $contentId, $viewer)) {
            return response()->json(['result' => ''], 403);
        }

        if ($type === 'comment' && ! $this->canViewCommentEntity($contentId, $viewer)) {
            return response()->json(['result' => ''], 403);
        }

        $query = Like::query()
            ->where('user_id', $viewer->id)
            ->where('content_id', $contentId)
            ->where('likeable_type', $type);

        if ($query->exists()) {
            $query->delete();
            $liked = false;
        } else {
            Like::query()->create([
                'user_id' => $viewer->id,
                'content_id' => $contentId,
                'likeable_type' => $type,
                'time' => now(),
            ]);
            $liked = true;
        }

        return response()->json([
            'result' => Like::query()
                ->where('content_id', $contentId)
                ->where('likeable_type', $type)
                ->count(),
            'liked' => $liked,
        ]);
    }

    /**
     * Creates a repost of a supported entity on behalf of a user or community.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function shared(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $contentId = (int)$request->input('id');
        $type = (string)$request->input('shareable_type', 'comment');

        if (!$viewer || $contentId < 1 || $type === '') {
            return response()->json(['result' => ''], 422);
        }

        if (in_array($type, ['photo', 'video'], true) && ! $this->canViewMediaEntity($type, $contentId, $viewer)) {
            return response()->json(['result' => ''], 403);
        }

        if ($type === 'comment' && ! $this->canViewCommentEntity($contentId, $viewer)) {
            return response()->json(['result' => ''], 403);
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
            'shared' => true,
        ]);
    }

    /**
     * Searches for city for live search in forms.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Looks for sports for live search in forms.
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Returns current of the authorized user front.
     *
     * @param Request $request
     * @return JsonResponse
     */
    private function viewer(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        return $user;
    }

    /**
     * Returns team or group from an AJAX request by ID.
     *
     * @param Request $request
     * @return Community|null
     */
    private function communityFromRequest(Request $request): ?Community
    {
        $communityId = (int)$request->input('community_id', $request->input('id', 0));

        return $communityId > 0
            ? ($this->communities->findTeam($communityId) ?: $this->communities->findGroup($communityId))
            : null;
    }

    /**
     * Normalizes the incoming list of attachment IDs.
     */
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

    /**
     * Checks access to the owner's media section before AJAX loading.
     */
    private function canViewOwnerMedia(string $type, int $ownerId, string $section, ?User $viewer): bool
    {
        return match ($type) {
            'user' => $this->canViewUserSection($ownerId, $section, $viewer),
            'team' => $this->communities->canViewSection($this->communities->findTeam($ownerId), $viewer, $section),
            'group' => $this->communities->canViewSection($this->communities->findGroup($ownerId), $viewer, $section),
            'event' => ($event = $this->events->findActive($ownerId)) && (bool) ($this->events->permissions($event, $viewer)[$section] ?? true),
            default => true,
        };
    }

    /**
     * Checks access to the user profile section for AJAX scripts.
     */
    private function canViewUserSection(int $userId, string $section, ?User $viewer): bool
    {
        $profile = $this->profiles->profile($userId);

        if (! $profile) {
            return false;
        }

        $friendshipStatus = $this->friends->friendshipStatus($viewer?->id, $profile->id);
        $permissions = $this->profiles->permissions($profile, $viewer, $friendshipStatus);

        return (bool) ($permissions[$section] ?? false);
    }

    /**
     * Checks access to a specific photo or video based on the owner of the album.
     */
    private function canViewMediaEntity(string $type, int $contentId, ?User $viewer): bool
    {
        if ($type === 'photo') {
            /** @var Photo|null $photo */
            $photo = Photo::query()->with('album')->whereKey($contentId)->first();

            if (! $photo) {
                return false;
            }

            return ! $photo->album
                || $this->canViewOwnerMedia((string) $photo->album->photoalbumable_type, (int) $photo->album->owner_id, 'photo', $viewer);
        }

        if ($type === 'video') {
            /** @var Video|null $video */
            $video = Video::query()->with('album')->whereKey($contentId)->first();

            if (! $video) {
                return false;
            }

            return ! $video->album
                || $this->canViewOwnerMedia((string) $video->album->videoalbumable_type, (int) $video->album->owner_id, 'video', $viewer);
        }

        return true;
    }

    /**
     * Checks access to a comment through access to the entity it belongs to.
     */
    private function canViewCommentEntity(int $commentId, ?User $viewer): bool
    {
        /** @var Comment|null $comment */
        $comment = Comment::query()->whereKey($commentId)->first();

        if (! $comment) {
            return false;
        }

        $type = (string) $comment->commentable_type;
        $contentId = (int) $comment->content_id;

        return match ($type) {
            'user' => $this->canViewUserSection($contentId, 'wall', $viewer),
            'team', 'group', 'event' => $this->canViewOwnerMedia($type, $contentId, 'wall', $viewer),
            'photo', 'video' => $this->canViewMediaEntity($type, $contentId, $viewer),
            default => true,
        };
    }

    /**
     * Returns an empty response for a closed media section.
     */
    private function emptyMediaResponse(): JsonResponse
    {
        return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
    }


    /**
     * Renders HTML photos cards for AJAX response.
     *
     * @param $photos
     * @param User|null $viewer
     * @param bool $canManage
     * @return string
     * @throws \Throwable
     */
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

    /**
     * Renders HTML video cards for AJAX response.
     *
     * @param $videos
     * @param User|null $viewer
     * @param bool $canManage
     * @return string
     * @throws \Throwable
     */
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

    /**
     * Adds to the list of communities data about permissions and status of the current user.
     *
     * @param Collection $items
     * @param User $viewer
     * @return Collection
     */
    private function communitiesForViewer(Collection $items, User $viewer): Collection
    {
        return $items->map(function (array $item) use ($viewer): array {
            $role = $this->communities->role((int)$item['id'], $viewer->id);

            $item['status'] = $this->communities->roleLabel($role);
            $item['can_edit'] = $role === 1;

            return $item;
        });
    }

    /**
     * Renders HTML teams or groups cards for an AJAX response.
     *
     * @param Collection $items
     * @param string $type
     * @param bool $inviteActions
     * @return string
     */
    private function renderCommunities(Collection $items, string $type, bool $inviteActions = false): string
    {
        $view = $type === 'team' ? 'front.teams._team-card' : 'front.groups._group-card';
        $key = $type === 'team' ? 'team' : 'group';

        return $items
            ->map(fn(array $item): string => view($view, [
                $key => $item,
                'inviteActions' => $inviteActions,
            ])->render())
            ->implode('');
    }

    /**
     * Renders HTML event cards for AJAX response
     *
     * @param Collection $items
     * @return string
     */
    private function renderEvents(Collection $items): string
    {
        return $items
            ->map(fn(array $event): string => view('front.events._event-card', ['event' => $event])->render())
            ->implode('');
    }


    /**
     * Renders HTML sports-blocks cards for AJAX response.
     *
     * @param Collection $items
     * @param string $routePrefix
     * @param string $type
     * @return string
     * @throws \Throwable
     */
    private function renderSportBlocks(Collection $items, string $routePrefix, string $type): string
    {
        return view('front.sport-blocks._cards', [
            'items' => $items,
            'routePrefix' => $routePrefix,
            'viewer' => $this->viewer(),
            'editLabel' => $type === 'playground' ? 'edit playground' : 'Edit',
        ])->render();
    }


    /**
     * Collects teams and groups filters from an AJAX request.
     *
     * @param Request $request
     * @return array
     */
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

    /**
     * Collects event filters from an AJAX request
     *
     * @param Request $request
     * @return array
     */
    private function eventFilters(Request $request): array
    {
        $date = (string)$request->input('date', '');
        $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4));

        return [
            'place' => trim((string)$request->input('place', '')),
            'sport' => trim((string)$request->input('sport', '')),
            'search' => trim((string)$request->input('search', '')),
            'date' => $validDate ? $date : '',
            'id_place' => (int)$request->input('id_place', 0),
            'id_sport' => (int)$request->input('id_sport', 0),
        ];
    }

    /**
     * Collects sports block filters from an AJAX request.
     *
     * @param Request $request
     * @return array
     */
    private function sportBlockFilters(Request $request): array
    {
        return [
            'place' => trim((string)$request->input('place', '')),
            'search' => trim((string)$request->input('search', '')),
            'id_place' => (int)$request->input('id_place', 0),
        ];
    }

    /**
     * Validates the AJAX request rules from the FormRequest class.
     *
     * @param Request $request
     * @param string $requestClass
     * @return array
     */
    private function validateAjax(Request $request, string $requestClass): array
    {
        $formRequest = app($requestClass);

        return Validator::make(
            $request->all(),
            $formRequest->rules(),
            method_exists($formRequest, 'messages') ? $formRequest->messages() : [],
            method_exists($formRequest, 'attributes') ? $formRequest->attributes() : [],
        )->validate();
    }
}
