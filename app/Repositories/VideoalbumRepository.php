<?php

namespace App\Repositories;

use App\DTO\Album\AlbumData;
use App\DTO\Video\VideoData;
use App\Helpers\StringHelper;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Models\VideoAlbums;
use App\Repositories\Concerns\DeletesContentRelations;
use App\Service\VideoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VideoalbumRepository extends BaseRepository
{
    use DeletesContentRelations;

    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(VideoAlbums $model, private readonly VideoService $videos)
    {
        parent::__construct($model);
    }

    /**
     * Возвращает альбомы пользователя.
     */
    public function albumsForUser(int $userId): Collection
    {
        return $this->albumsForOwner($userId, 'user');
    }

    /**
     * Возвращает альбомы указанного владельца и типа.
     *
     * @param int $ownerId
     * @param string $type
     * @return Collection
     */
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

    /**
     * Возвращает видео пользователя.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function videosForUser(int $userId, int $limit = 6, int $offset = 0): Collection
    {
        return $this->videosForOwner($userId, 'user', $limit, $offset);
    }

    /**
     * Возвращает видео указанного владельца и типа.
     *
     * @param int $ownerId
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
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

    /**
     * Проверяет, есть ли еще видео пользователя после текущей страницы.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreUserVideos(int $userId, int $limit, int $offset): bool
    {
        return $this->hasMoreOwnerVideos($userId, 'user', $limit, $offset);
    }

    /**
     * Проверяет, есть ли еще видео владельца после текущей страницы.
     *
     * @param int $ownerId
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @return bool
     */
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

    /**
     * Возвращает популярные видео выбранного типа.
     *
     * @param int $limit
     * @param int $offset
     * @param string $type
     * @return Collection
     */
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

    /**
     * Находит альбом по id и допустимым типам владельца.
     *
     * @param int $albumId
     * @param array|null $types
     * @return VideoAlbums|null
     */
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

    /**
     * Возвращает видео выбранного альбома постранично.
     *
     * @param VideoAlbums $album
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
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

    /**
     * Проверяет, есть ли еще видео в альбоме после текущей страницы.
     *
     * @param VideoAlbums $album
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreAlbumVideos(VideoAlbums $album, int $limit, int $offset): bool
    {
        return $album->videos()
            ->where('banned', false)
            ->orderByDesc('id')
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    /**
     * Возвращает альбомы, доступные пользователю для редактирования.
     *
     * @param User $user
     * @return Collection
     */

    public function editableAlbumsFor(User $user): Collection
    {
        return $this->editableAlbumsForOwner($user->id, 'user');
    }

    /**
     * Возвращает альбомы владельца, доступные для редактирования.
     *
     * @param int $ownerId
     * @param string $type
     * @return Collection
     */
    public function editableAlbumsForOwner(int $ownerId, string $type): Collection
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('videoalbumable_type', $type)
            ->orderBy('name')
            ->get();
    }

    /**
     * Возвращает альбом пользователя по умолчанию или создает его.
     *
     * @param User $user
     * @return VideoAlbums
     */
    public function ensureDefaultAlbum(User $user): VideoAlbums
    {
        return $this->ensureDefaultAlbumForOwner($user->id, 'user', 'Мой альбом');
    }

    /**
     * Возвращает альбом владельца по умолчанию или создает его.
     *
     * @param int $ownerId
     * @param string $type
     * @param string $name
     * @return VideoAlbums
     */
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

    /**
     * Создает альбом пользователя.
     *
     * @param User $user
     * @param AlbumData $data
     * @return VideoAlbums
     */
    public function createUserAlbum(User $user, AlbumData $data): VideoAlbums
    {
        return $this->createAlbumForOwner($user->id, 'user', $data);
    }

    /**
     * Создает альбом для указанного владельца и типа.
     *
     * @param int $ownerId
     * @param string $type
     * @param AlbumData $data
     * @return VideoAlbums
     */
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

    /**
     * Обновляет название пользовательского альбома.
     *
     * @param VideoAlbums $album
     * @param AlbumData $data
     * @return bool
     */
    public function updateUserAlbum(VideoAlbums $album, AlbumData $data): bool
    {
        return $album->fill($data->toArray())->save();
    }

    /**
     * Проверяет, занято ли имя альбома у пользователя.
     *
     * @param User $user
     * @param string $name
     * @param int|null $exceptId
     * @return bool
     */
    public function nameExists(User $user, string $name, ?int $exceptId = null): bool
    {
        return $this->nameExistsForOwner($user->id, 'user', $name, $exceptId);
    }

    /**
     * Проверяет, занято ли имя альбома у владельца.
     *
     * @param int $ownerId
     * @param string $type
     * @param string $name
     * @param int|null $exceptId
     * @return bool
     */
    public function nameExistsForOwner(int $ownerId, string $type, string $name, ?int $exceptId = null): bool
    {
        return $this->model->newQuery()
            ->where('owner_id', $ownerId)
            ->where('videoalbumable_type', $type)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    /**
     * Проверяет, является ли пользователь владельцем сущности.
     *
     * @param VideoAlbums $album
     * @param User|null $user
     * @return bool
     */
    public function isOwner(VideoAlbums $album, ?User $user): bool
    {
        return $user && (int) $album->owner_id === (int) $user->id;
    }

    /**
     * Добавляет видео пользователя в выбранный альбом.
     *
     * @param User $user
     * @param VideoAlbums $album
     * @param VideoData $data
     * @return Video
     */
    public function addUserVideo(User $user, VideoAlbums $album, VideoData $data): Video
    {
        if (! $this->isOwner($album, $user) || $album->videoalbumable_type !== 'user') {
            throw new RuntimeException('Нет доступа к выбранному альбому.');
        }

        return $this->addVideoToAlbum($user, $album, $data);
    }

    /**
     * Добавляет видео в альбом владельца и сохраняет данные ролика.
     *
     * @param User $user
     * @param VideoAlbums $album
     * @param VideoData $data
     * @return Video
     */
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

    /**
     * Удаляет альбом и связанные с ним материалы.
     *
     * @param VideoAlbums $album
     * @return bool
     * @throws \Throwable
     */
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

    /**
     * Удаляет видео пользователя, если он является владельцем.
     *
     * @param User $user
     * @param int $videoId
     * @return bool
     */
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

    /**
     * Удаляет видео вместе со связанными данными.
     *
     * @param Video $video
     * @return bool
     * @throws \Throwable
     */
    public function deleteVideo(Video $video): bool
    {
        return DB::transaction(function () use ($video): bool {
            $this->deleteVideoRelations($video);

            return (bool) $video->delete();
        });
    }

    /**
     * Преобразует видео в массив данных для вывода.
     *
     * @param Video $video
     * @return array
     */
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

    /**
     * Преобразует альбом в массив данных для вывода.
     *
     * @param VideoAlbums $album
     * @return array
     */
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

    /**
     * Удаляет реакции, комментарии и вложения видео.
     *
     * @param Video $video
     * @return void
     */
    private function deleteVideoRelations(Video $video): void
    {
        $this->deleteContentRelations('video', (int) $video->id);

        VideoView::query()
            ->where('video_id', $video->id)
            ->delete();
    }
}
