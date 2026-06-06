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
use App\Repositories\FriendRepository;
use App\Repositories\NewsRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AjaxController extends Controller
{
    public function __construct(
        private readonly NewsRepository $news,
        private readonly FriendRepository $friends,
        private readonly UserRepository $users,
        private readonly ProfileRepository $profiles,
    ) {
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
            'add_photo_ajax_attach' => $this->addPhotoAjaxAttach($request),
            'uploadavatar' => $this->uploadAvatar($request),
            'addcomment' => $this->addComment($request),
            'removecomment' => $this->removeComment($request),
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
        $limit = min(max((int) $request->input('number', 5), 1), 25);
        $offset = max((int) $request->input('offset', 0), 0);
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

        if (! $viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int) $request->input('number', 6), 1), 24);
        $users = $this->friends->possibleFriendsFor($viewer->id, $limit);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function getFriendsList(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (! $viewer) {
            return response()->json(['item' => []], 401);
        }

        $limit = min(max((int) $request->input('number', 10), 1), 24);
        $offset = max((int) $request->input('offset', 0), 0);
        $userId = (int) $request->input('user_id', $viewer->id);
        $users = $this->friends->friendsFor($userId, $limit, $offset);

        return response()->json([
            'item' => $this->friends->serializeUsers($users, $viewer->id),
        ]);
    }

    private function addAsFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1 || ! $this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->requestFriendship($viewer->id, $friendId),
        ]);
    }

    private function acceptFriendship(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1 || ! $this->users->findActive($friendId)) {
            return response()->json(['status' => null], 422);
        }

        return response()->json([
            'status' => $this->friends->acceptFriendship($viewer->id, $friendId),
        ]);
    }

    private function removeFriend(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1) {
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
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1 || ! $this->users->findActive($friendId)) {
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
        $friendId = (int) $request->input('user_id');

        if (! $viewer || $friendId < 1) {
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
        $type = (string) $request->input('commentable_type', 'user');
        $profileId = (int) $request->input('id', $request->input('content_id', 0));
        $limit = min(max((int) $request->input('number', 10), 1), 25);
        $offset = max((int) $request->input('offset', 0), 0);

        if (! in_array($type, ['user', 'photo'], true) || $profileId < 1) {
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
        $photoId = (int) $request->input('photo_id', $request->input('id', 0));

        if ($photoId < 1) {
            return response()->json(['status' => 0]);
        }

        /** @var Photo|null $photo */
        $photo = Photo::query()
            ->with(['owner', 'album'])
            ->whereKey($photoId)
            ->where('banned', false)
            ->first();

        if (! $photo) {
            return response()->json(['status' => 0]);
        }

        $photoUrl = FrontAssets::photoGallery($photo, 'photo') ?: FrontAssets::photoGallery($photo);

        if (! $photoUrl) {
            return response()->json(['status' => 0]);
        }

        $owner = $photo->owner;

        return response()->json([
            'status' => 1,
            'owner_id' => (int) ($owner?->id ?? $photo->owner_id),
            'firstname' => (string) ($owner?->firstname ?? ''),
            'lastname' => (string) ($owner?->lastname ?? ''),
            'created' => $photo->created_at?->format('d.m.Y H:i') ?? '',
            'description' => (string) $photo->description,
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

    private function addPhotoAjaxAttach(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (! $viewer) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
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

        if (! $disk->put($originalPath, $contents) || ! $disk->put($smallPath, $contents)) {
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
            'num' => (int) $request->input('num', 0),
            'message' => [
                'id' => (int) $photo->id,
                'small_photo' => FrontAssets::photoGallery($photo),
                'photo' => FrontAssets::photoGallery($photo, 'photo'),
            ],
        ]);
    }

    private function uploadAvatar(Request $request): JsonResponse
    {
        $viewer = $this->viewer();

        if (! $viewer) {
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
        $type = (string) $request->input('commentable_type', 'user');
        $profileId = (int) $request->input('content_id');
        $comment = trim((string) $request->input('comment', ''));
        $attach = $request->input('attach', []);

        if (! $viewer || ! in_array($type, ['user', 'photo'], true) || $profileId < 1 || ($comment === '' && empty($attach))) {
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
        $commentId = (int) $request->input('id_comment', $request->input('id', 0));

        if (! $viewer || $commentId < 1) {
            return response()->json(['result' => ''], 422);
        }

        if (! $this->profiles->deleteComment($viewer, $commentId)) {
            return response()->json(['result' => ''], 403);
        }

        return response()->json(['result' => 'success']);
    }

    private function liked(Request $request): JsonResponse
    {
        $viewer = $this->viewer();
        $contentId = (int) $request->input('id');
        $type = (string) $request->input('likeable_type', 'comment');

        if (! $viewer || $contentId < 1 || $type === '') {
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
        $contentId = (int) $request->input('id');
        $type = (string) $request->input('shareable_type', 'comment');

        if (! $viewer || $contentId < 1 || $type === '') {
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
        $city = trim((string) $request->query('city', ''));

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
            ->map(fn (GeoCity $city): array => [
                'id' => $city->id,
                'name' => $city->name_ru,
            ]);

        return response()->json(['item' => $items]);
    }

    private function searchSportTypes(Request $request): JsonResponse
    {
        $sportTypes = trim((string) $request->query('sport_types', ''));

        if ($sportTypes === '') {
            return response()->json(['item' => []]);
        }

        $items = SportType::query()
            ->where('name', 'like', '%' . $sportTypes . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn (SportType $sportType): array => [
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
}
