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
use App\Models\Videoalbum;
use App\Repositories\FriendRepository;
use App\Repositories\CommunityRepository;
use App\Repositories\MessageRepository;
use App\Repositories\NewsRepository;
use App\Repositories\PhotoalbumRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\SportBlockRepository;
use App\Repositories\UserRepository;
use App\Repositories\VideoalbumRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        private readonly SportBlockRepository $sportBlocks,
    )
    {
    }

    public function handle(Request $request, string $action): JsonResponse
    {
        return match ($action) {
            'get_usernews_list' => $this->getUserNewsList($request),
            'getpossiblefriends' => $this->getPossibleFriends($request),
            'get_friends_list' => $this->getFriendsList($request),
            'add_as_friend' => $this->addAsFriend($request),
            'accept_friendship' => $this->acceptFriendship($request),
            'remove_friend' => $this->removeFriend($request),
            'block_user' => $this->blockUser($request),
            'unblock_user' => $this->unblockUser($request),
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

    private function getComments(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $type = (string)$request->input('commentable_type', 'user');
        $profileId = (int)$request->input('id', $request->input('content_id', 0));
        $limit = min(max((int)$request->input('number', 10), 1), 25);
        $offset = max((int)$request->input('offset', 0), 0);

        if (!in_array($type, ['user', 'photo', 'video', 'team'], true) || $profileId < 1) {
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
        $sportBlockTypes = ['playground', 'shop', 'fitness'];
        $album = match (true) {
            $albumType === 'team' => $this->photoalbums->album($albumId, ['team']),
            in_array($albumType, $sportBlockTypes, true) => $this->photoalbums->album($albumId, $sportBlockTypes),
            default => $this->photoalbums->album($albumId),
        };

        if (!$album) {
            return response()->json(['info' => null, 'error' => 'Нет доступа к альбому'], 403);
        }

        $canUpload = match (true) {
            $album->photoalbumable_type === 'team' => $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer),
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
            if ($album->photoalbumable_type === 'team' || in_array($album->photoalbumable_type, $sportBlockTypes, true)) {
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

        if ($ownerId < 1 || !in_array($type, ['user', 'team'], true)) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $photos = $type === 'team'
            ? $this->photoalbums->photosForOwner($ownerId, 'team', $limit, $offset)
            : $this->photoalbums->photosForUser($ownerId, $limit, $offset);

        return response()->json([
            'status' => $photos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderPhotos($photos, $viewer, $type === 'team' && $this->communities->canManage($this->communities->findTeam($ownerId), $viewer)),
            'has_more' => $type === 'team'
                ? $this->photoalbums->hasMoreOwnerPhotos($ownerId, 'team', $limit, $offset)
                : $this->photoalbums->hasMoreUserPhotos($ownerId, $limit, $offset),
        ]);
    }

    private function getAlbumPhotos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 9), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->photoalbums->album((int)$request->input('id_album'), ['user', 'user_attach', 'team']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $photos = $this->photoalbums->albumPhotos($album, $limit, $offset);

        return response()->json([
            'status' => $photos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderPhotos($photos, $viewer, $album->photoalbumable_type === 'team'
                ? $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer)
                : $this->photoalbums->isOwner($album, $viewer)),
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

        $photo = $this->photoalbums->photo($photoId, ['user', 'user_attach', 'team']);

        if ($photo && $photo->album?->photoalbumable_type === 'team') {
            $team = $this->communities->findTeam((int)$photo->album->owner_id);

            return response()->json([
                'result' => $team && $this->communities->canManage($team, $viewer) && $this->photoalbums->deletePhoto($photo)
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

        if (!$video || !$video->album || !in_array($video->album->videoalbumable_type, ['user', 'team'], true)) {
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

        if ($ownerId < 1 || !in_array($type, ['user', 'team'], true)) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $videos = $type === 'team'
            ? $this->videoalbums->videosForOwner($ownerId, 'team', $limit, $offset)
            : $this->videoalbums->videosForUser($ownerId, $limit, $offset);

        return response()->json([
            'status' => $videos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderVideos($videos, $viewer, $type === 'team' && $this->communities->canManage($this->communities->findTeam($ownerId), $viewer)),
            'has_more' => $type === 'team'
                ? $this->videoalbums->hasMoreOwnerVideos($ownerId, 'team', $limit, $offset)
                : $this->videoalbums->hasMoreUserVideos($ownerId, $limit, $offset),
        ]);
    }

    private function getAlbumVideos(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $limit = min(max((int)$request->input('number', 6), 1), 30);
        $offset = max((int)$request->input('offset', 0), 0);
        $album = $this->videoalbums->album((int)$request->input('id_album'), ['user', 'team']);

        if (!$album) {
            return response()->json(['status' => 0, 'html' => '', 'has_more' => false]);
        }

        $videos = $this->videoalbums->albumVideos($album, $limit, $offset);

        return response()->json([
            'status' => $videos->isNotEmpty() ? 1 : 0,
            'html' => $this->renderVideos($videos, $viewer, $album->videoalbumable_type === 'team'
                ? $this->communities->canManage($this->communities->findTeam((int)$album->owner_id), $viewer)
                : $this->videoalbums->isOwner($album, $viewer)),
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

        if ($video && $video->album?->videoalbumable_type === 'team') {
            $team = $this->communities->findTeam((int)$video->album->owner_id);

            return response()->json([
                'result' => $team && $this->communities->canManage($team, $viewer) && $this->videoalbums->deleteVideo($video)
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

    private function addComment(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $type = (string)$request->input('commentable_type', 'user');
        $profileId = (int)$request->input('content_id');
        $comment = trim((string)$request->input('comment', ''));
        $attach = $request->input('attach', []);

        if (!$viewer || !in_array($type, ['user', 'photo', 'video', 'team'], true) || $profileId < 1 || ($comment === '' && empty($attach))) {
            return response()->json([
                'status' => false,
                'errors' => ['comment' => 'Заполните комментарий'],
            ], 422);
        }

        $created = $this->profiles->createWallComment($viewer, [
            'commentable_type' => $type,
            'content_id' => $profileId,
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
}
