<?php

namespace App\Repositories;

use App\DTO\Album\AlbumData;
use App\DTO\Video\VideoData;
use App\Helpers\StringHelper;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Share;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Models\VideoAlbums;
use App\Service\VideoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VideoalbumRepository extends BaseRepository
{
    public function __construct(VideoAlbums $model, private readonly VideoService $videos)
    {
        parent::__construct($model);
    }

    public function albumsForUser(int $userId): Collection
    {
        return $this->albumsForOwner($userId, 'user');
    }

    public function albumsForOwner(int $ownerId, string $type): Collection
    {
        return $this->model->newQuery()
            ->with(['videos' => fn ($query) => $query
                ->where('banned', false)
                ->latest('id')
                ->limit(1)])
            ->where('owner_id', $ownerId)
            ->where('videoalbumable_type', $type)
            ->orderByDesc('id')
            ->get()
            ->map(fn (VideoAlbums $album): array => $this->serializeAlbum($album));
    }

    public function videosForUser(int $userId, int $limit = 6, int $offset = 0): Collection
    {
        return $this->videosForOwner($userId, 'user', $limit, $offset);
    }

    public function videosForOwner(int $ownerId, string $type, int $limit = 6, int $offset = 0): Collection
    {
        return Video::query()
            ->with(['album'])
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $ownerId)
                ->where('videoalbumable_type', $type))
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Video $video): array => $this->serializeVideo($video));
    }

    public function hasMoreUserVideos(int $userId, int $limit, int $offset): bool
    {
        return $this->hasMoreOwnerVideos($userId, 'user', $limit, $offset);
    }

    public function hasMoreOwnerVideos(int $ownerId, string $type, int $limit, int $offset): bool
    {
        return Video::query()
            ->where('banned', false)
            ->whereHas('album', fn ($query) => $query
                ->where('owner_id', $ownerId)
                ->where('videoalbumable_type', $type))
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    public function popularVideos(int $limit = 6, int $offset = 0, string $type = 'user'): Collection
    {
        return Video::query()
            ->with(['album'])
            ->select('videos.*')
            ->leftJoin('likes', function ($join): void {
                $join->on('likes.content_id', '=', 'videos.id')
                    ->where('likes.likeable_type', 'video');
            })
            ->leftJoin('video_views', 'video_views.video_id', '=', 'videos.id')
            ->join('videoalbums', 'videoalbums.id', '=', 'videos.videoalbum_id')
            ->where('videos.banned', false)
            ->where('videoalbums.videoalbumable_type', $type)
            ->groupBy(
                'videos.id',
                'videos.videoalbum_id',
                'videos.provider',
                'videos.video',
                'videos.description',
                'videos.owner_id',
                'videos.banned',
                'videos.created_at',
                'videos.updated_at',
            )
            ->orderByRaw('COUNT(DISTINCT likes.id) DESC')
            ->orderByRaw('COUNT(DISTINCT video_views.id) DESC')
            ->orderByDesc('videos.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Video $video): array => $this->serializeVideo($video));
    }

    public function album(int $albumId, ?array $types = null): ?VideoAlbums
    {
        /** @var VideoAlbums|null $album */
        $album = $this->model->newQuery()
            ->with('owner.settings')
            ->whereKey($albumId)
            ->whereIn('videoalbumable_type', $types ?: ['user'])
            ->first();

        return $album;
    }

    public function albumVideos(VideoAlbums $album, int $limit = 6, int $offset = 0): Collection
    {
        return $album->videos()
            ->with('album')
            ->where('banned', false)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Video $video): array => $this->serializeVideo($video));
    }

    public function hasMoreAlbumVideos(VideoAlbums $album, int $limit, int $offset): bool
    {
        return $album->videos()
            ->where('banned', false)
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    public function editableAlbumsFor(User $user): Collection
    {
        return $this->editableAlbumsForOwner($user->id, 'user');
    }

    public function editableAlbumsForOwner(int $ownerId, string $type): Collection
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('videoalbumable_type', $type)
            ->orderBy('name')
            ->get();
    }

    public function ensureDefaultAlbum(User $user): VideoAlbums
    {
        return $this->ensureDefaultAlbumForOwner($user->id, 'user', 'Мой альбом');
    }

    public function ensureDefaultAlbumForOwner(int $ownerId, string $type, string $name = 'Мой альбом'): VideoAlbums
    {
        /** @var VideoAlbums $album */
        $album = $this->model->newQuery()->firstOrCreate([
            'owner_id' => $ownerId,
            'videoalbumable_type' => $type,
        ], [
            'name' => $name,
        ]);

        return $album;
    }

    public function createUserAlbum(User $user, AlbumData $data): VideoAlbums
    {
        return $this->createAlbumForOwner($user->id, 'user', $data);
    }

    public function createAlbumForOwner(int $ownerId, string $type, AlbumData $data): VideoAlbums
    {
        /** @var VideoAlbums $album */
        $album = $this->model->newQuery()->create([
            'name' => $data->name,
            'videoalbumable_type' => $type,
            'owner_id' => $ownerId,
        ]);

        return $album;
    }

    public function updateUserAlbum(VideoAlbums $album, AlbumData $data): bool
    {
        return $album->fill($data->toArray())->save();
    }

    public function nameExists(User $user, string $name, ?int $exceptId = null): bool
    {
        return $this->nameExistsForOwner($user->id, 'user', $name, $exceptId);
    }

    public function nameExistsForOwner(int $ownerId, string $type, string $name, ?int $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('videoalbumable_type', $type)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function isOwner(VideoAlbums $album, ?User $user): bool
    {
        return $user && (int) $album->owner_id === (int) $user->id;
    }

    public function addUserVideo(User $user, VideoAlbums $album, VideoData $data): Video
    {
        if (! $this->isOwner($album, $user) || $album->videoalbumable_type !== 'user') {
            throw new RuntimeException('Нет доступа к выбранному альбому.');
        }

        return $this->addVideoToAlbum($user, $album, $data);
    }

    public function addVideoToAlbum(User $user, VideoAlbums $album, VideoData $data): Video
    {
        $videoData = $this->videos->detectVideo($data->link);

        if (! $videoData) {
            throw new RuntimeException('Укажите корректную ссылку на YouTube-видео.');
        }

        /** @var Video $video */
        $video = Video::query()->create([
            'videoalbum_id' => $album->id,
            'provider' => $videoData['provider'],
            'video' => $videoData['video'],
            'description' => $data->description,
            'owner_id' => in_array($album->videoalbumable_type, ['team', 'group', 'event'], true) ? $album->owner_id : $user->id,
            'banned' => false,
        ])->load('album');

        return $video;
    }

    public function deleteAlbum(VideoAlbums $album): bool
    {
        return DB::transaction(function () use ($album): bool {
            $album->loadMissing('videos');

            foreach ($album->videos as $video) {
                $this->deleteVideoRelations($video);
                $video->delete();
            }

            return (bool) $album->delete();
        });
    }

    public function deleteVideoFor(User $user, int $videoId): bool
    {
        /** @var Video|null $video */
        $video = Video::query()
            ->with('album')
            ->whereKey($videoId)
            ->first();

        if (! $video || (int) $video->owner_id !== (int) $user->id) {
            return false;
        }

        return $this->deleteVideo($video);
    }

    public function deleteVideo(Video $video): bool
    {
        return DB::transaction(function () use ($video): bool {
            $this->deleteVideoRelations($video);

            return (bool) $video->delete();
        });
    }

    public function serializeVideo(Video $video): array
    {
        $video->loadMissing('album');

        return [
            'id' => (int) $video->id,
            'thumb' => StringHelper::thumbUrl((string) $video->provider, (string) $video->video),
            'player' => $this->videos->playerHtml((string) $video->provider, (string) $video->video),
            'description' => (string) $video->description,
            'owner_id' => (int) $video->owner_id,
            'views_count' => (int) $video->views()->count(),
            'type' => (string) ($video->album?->videoalbumable_type ?? 'user'),
        ];
    }

    public function serializeAlbum(VideoAlbums $album): array
    {
        $album->loadMissing(['videos' => fn ($query) => $query
            ->where('banned', false)
            ->latest('id')
            ->limit(1)]);

        /** @var Video|null $video */
        $video = $album->videos->first();

        return [
            'id' => (int) $album->id,
            'name' => (string) $album->name,
            'type' => (string) $album->videoalbumable_type,
            'owner_id' => (int) $album->owner_id,
            'image' => $video ? StringHelper::thumbUrl((string) $video->provider, (string) $video->video) : null,
        ];
    }

    private function deleteVideoRelations(Video $video): void
    {
        Comment::query()
            ->where('commentable_type', 'video')
            ->where('content_id', $video->id)
            ->delete();

        Like::query()
            ->where('likeable_type', 'video')
            ->where('content_id', $video->id)
            ->delete();

        Share::query()
            ->where('shareable_type', 'video')
            ->where('content_id', $video->id)
            ->delete();

        VideoView::query()
            ->where('video_id', $video->id)
            ->delete();
    }
}
