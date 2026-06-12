<?php

namespace App\Repositories;

use App\Helpers\StringHelper;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\NewsRssSport;
use App\Models\Photo;
use App\Models\Share;
use App\Models\Video;
use App\Service\NewsFeedFormatterService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NewsRepository extends BaseRepository
{
    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(
        NewsRssSport $model,
        private readonly NewsFeedFormatterService $formatter
    )
    {
        parent::__construct($model);
    }

    /**
     * Возвращает последние новости RSS.
     *
     * @param int $limit
     * @return Collection
     */
    public function latest(int $limit = 20): Collection
    {
        return $this->feed($limit);
    }

    /**
     * Собирает общую ленту новостей.
     */
    public function feed(int $limit = 25): Collection
    {
        return $this->feedPage($limit);
    }

    /**
     * Возвращает страницу ленты новостей с учетом лимита и смещения.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public function feedPage(int $limit = 5, int $offset = 0): Collection
    {
        $items = collect()
            ->merge($this->photoItems($limit, $offset))
            ->merge($this->videoItems($limit, $offset))
            ->merge($this->commentItems($limit, $offset))
            ->merge($this->photoCommentItems($limit, $offset))
            ->merge($this->videoCommentItems($limit, $offset))
            ->sortByDesc('time')
            ->unique('event_key')
            ->values();

        return $this->withActionCounts($items);
    }

    /**
     * Возвращает элементы ленты по новым фотографиям.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    private function photoItems(int $limit, int $offset = 0): Collection
    {
        return Photo::query()
            ->from('photos as p')
            ->leftJoin('photoalbums as pa', 'pa.id', '=', 'p.photoalbum_id')
            ->join('users as u', 'u.id', '=', 'p.owner_id')
            ->select([
                'p.id',
                'p.small_photo',
                'p.photo',
                'p.created_at',
                'pa.photoalbumable_type',
                'u.id as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.sex',
                'u.avatar',
                'u.banned',
                'u.deleted',
            ])
            ->where('p.banned', false)
            ->where('p.moderate', false)
            ->whereNotNull('p.owner_id')
            ->where(function ($query) {
                $query->whereNull('pa.photoalbumable_type')
                    ->orWhere('pa.photoalbumable_type', '<>', 'user_attach');
            })
            ->where(function ($query) {
                $query->whereNotNull('p.small_photo')
                    ->orWhereNotNull('p.photo');
            })
            ->orderByDesc('p.created_at')
            ->orderByDesc('p.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->makeUserItem($row, [
                'event_key' => 'photo-publish:' . (int) $row->id,
                'content_id' => (int) $row->id,
                'likeable_type' => 'photo',
                'message' => sprintf(
                    '<p class="mess_news">Опубликовал(а) фото:</p><br> <ul class="attach_image"><li><img class="photo_big" alt="" src="%s" data-num="%d"></li></ul>',
                    e($this->formatter->photoUrl($row->small_photo ?: $row->photo, $row->photoalbumable_type)),
                    (int) $row->id
                ),
            ]));
    }

    /**
     * Возвращает элементы ленты по новым видео.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    private function videoItems(int $limit, int $offset = 0): Collection
    {
        return Video::query()
            ->from('videos as v')
            ->leftJoin('videoalbums as va', 'va.id', '=', 'v.videoalbum_id')
            ->join('users as u', 'u.id', '=', 'v.owner_id')
            ->select([
                'v.id',
                'v.provider',
                'v.video',
                'v.created_at',
                'va.videoalbumable_type',
                'u.id as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.sex',
                'u.avatar',
                'u.banned',
                'u.deleted',
            ])
            ->where('v.banned', false)
            ->whereNotNull('v.owner_id')
            ->whereNotNull('v.video')
            ->orderByDesc('v.created_at')
            ->orderByDesc('v.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->makeUserItem($row, [
                'event_key' => 'video-publish:' . (int) $row->id,
                'content_id' => (int) $row->id,
                'likeable_type' => 'video',
                'message' => sprintf(
                    '<p class="mess_news">Опубликовал(а) видео:</p><br> <ul class="attach_image"><li><img class="video_prev" alt="" src="%s" data-num="%d"></li></ul>',
                    e(StringHelper::videoThumbUrl($row->provider, $row->video)),
                    (int) $row->id
                ),
            ]));
    }

    /**
     * Возвращает элементы ленты по комментариям к стене.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    private function commentItems(int $limit, int $offset = 0): Collection
    {
        return Comment::query()
            ->from('comments as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->select([
                'c.id',
                'c.content',
                'c.created_at',
                'u.id as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.sex',
                'u.avatar',
                'u.banned',
                'u.deleted',
            ])
            ->where('c.commentable_type', 'user')
            ->whereNotNull('c.user_id')
            ->where(function ($query) {
                $query->where('c.content', '<>', '')
                    ->orWhereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('attachment as a')
                            ->whereColumn('a.content_id', 'c.id')
                            ->where('a.type', 'comment');
                    });
            })
            ->orderByDesc('c.created_at')
            ->orderByDesc('c.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->makeUserItem($row, [
                'event_key' => 'user-comment:' . (int) $row->id,
                'content_id' => (int) $row->id,
                'likeable_type' => 'comment',
                'message' => '<p class="mess_news">Оставил(a) комментарий на своей странице:</p> '
                    . nl2br($this->formatter->safeText((string) $row->content))
                    . $this->commentAttachmentsHtml((int) $row->id),
            ]));
    }

    /**
     * Возвращает элементы ленты по комментариям к фотографиям.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    private function photoCommentItems(int $limit, int $offset = 0): Collection
    {
        return Comment::query()
            ->from('comments as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->join('photos as p', 'p.id', '=', 'c.content_id')
            ->leftJoin('photoalbums as pa', 'pa.id', '=', 'p.photoalbum_id')
            ->join('users as owner', 'owner.id', '=', 'p.owner_id')
            ->select([
                'c.id',
                'c.content',
                'c.created_at',
                'p.id as photo_id',
                'p.small_photo',
                'p.photo',
                'p.created_at as content_created_at',
                'pa.photoalbumable_type',
                'owner.id as owner_id',
                'owner.firstname as owner_firstname',
                'owner.lastname as owner_lastname',
                'owner.email as owner_email',
                'owner.sex as owner_sex',
                'owner.avatar as owner_avatar',
                'owner.banned as owner_banned',
                'owner.deleted as owner_deleted',
                'u.id as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.sex',
                'u.avatar',
                'u.banned',
                'u.deleted',
            ])
            ->where('c.commentable_type', 'photo')
            ->where('p.banned', false)
            ->whereNotNull('c.user_id')
            ->where('c.content', '<>', '')
            ->orderByDesc('c.created_at')
            ->orderByDesc('c.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->makeUserItem($row, [
                'event_key' => 'photo-comment:' . (int) $row->id,
                'content_id' => (int) $row->id,
                'likeable_type' => 'photo',
                'message' => sprintf(
                    '<p class="mess_news">Прокомментировал(а) фото: <br><br>%s</p> <div class="art_mess"><a href="%s"><div class="message-account"><div class="head-img"><img src="%s" alt=""></div><p class="head-topic">%s</p><p class="data">%s</p></div></a><div style="clear:both"> <ul class="attach_image"><li><img class="photo_big" alt="" src="%s" data-num="%d"></li></ul></div></div>',
                    nl2br($this->formatter->safeText((string) $row->content)),
                    e(route('front.profile.show', ['user' => (int) $row->owner_id])),
                    e($this->formatter->ownerAvatar($row)),
                    e($this->formatter->ownerName($row)),
                    e(StringHelper::russianDate(Carbon::parse($row->content_created_at ?: $row->created_at))),
                    e($this->formatter->photoUrl($row->small_photo ?: $row->photo, $row->photoalbumable_type)),
                    (int) $row->photo_id
                ),
            ]));
    }

    /**
     * Возвращает элементы ленты по комментариям к видео.
     *
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    private function videoCommentItems(int $limit, int $offset = 0): Collection
    {
        return Comment::query()
            ->from('comments as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->join('videos as v', 'v.id', '=', 'c.content_id')
            ->join('users as owner', 'owner.id', '=', 'v.owner_id')
            ->select([
                'c.id',
                'c.content',
                'c.created_at',
                'v.id as video_id',
                'v.provider',
                'v.video',
                'v.created_at as content_created_at',
                'owner.id as owner_id',
                'owner.firstname as owner_firstname',
                'owner.lastname as owner_lastname',
                'owner.email as owner_email',
                'owner.sex as owner_sex',
                'owner.avatar as owner_avatar',
                'owner.banned as owner_banned',
                'owner.deleted as owner_deleted',
                'u.id as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.sex',
                'u.avatar',
                'u.banned',
                'u.deleted',
            ])
            ->where('c.commentable_type', 'video')
            ->where('v.banned', false)
            ->whereNotNull('c.user_id')
            ->where('c.content', '<>', '')
            ->orderByDesc('c.created_at')
            ->orderByDesc('c.id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->makeUserItem($row, [
                'event_key' => 'video-comment:' . (int) $row->id,
                'content_id' => (int) $row->id,
                'likeable_type' => 'video',
                'message' => sprintf(
                    '<p class="mess_news">Прокомментировал(а) видео: <br><br>%s</p> <div class="art_mess"><a href="%s"><div class="message-account"><div class="head-img"><img src="%s" alt=""></div><p class="head-topic">%s</p><p class="data">%s</p></div></a><div style="clear:both"> <ul class="attach_image"><li><img class="video_prev" alt="" src="%s" data-num="%d"></li></ul></div></div>',
                    nl2br($this->formatter->safeText((string) $row->content)),
                    e(route('front.profile.show', ['user' => (int) $row->owner_id])),
                    e($this->formatter->ownerAvatar($row)),
                    e($this->formatter->ownerName($row)),
                    e(StringHelper::russianDate(Carbon::parse($row->content_created_at ?: $row->created_at))),
                    e(StringHelper::videoThumbUrl($row->provider, $row->video)),
                    (int) $row->video_id
                ),
            ]));
    }

    /**
     * Формирует элемент ленты от имени пользователя.
     *
     * @param object $row
     * @param array $data
     * @return array
     */
    private function makeUserItem(object $row, array $data): array
    {
        $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();

        return [
            'author_id' => (int) $row->author_id,
            'author_name' => $this->userName($row),
            'avatar' => $this->formatter->userAvatar($row),
            'date' => StringHelper::russianDate($createdAt),
            'time' => $createdAt->getTimestamp(),
            'type' => 'user',
            'event_key' => $data['event_key'],
            'content_id' => $data['content_id'],
            'likeable_type' => $data['likeable_type'],
            'message' => $data['message'],
            'likes_count' => 0,
            'tells_count' => 0,
            'online' => false,
        ];
    }

    /**
     * Добавляет к элементам ленты счетчики лайков и комментариев.
     *
     * @param Collection $items
     * @return Collection
     */
    private function withActionCounts(Collection $items): Collection
    {
        $keys = $items
            ->filter(fn (array $item) => $item['likeable_type'] !== '' && $item['content_id'] > 0)
            ->groupBy('likeable_type')
            ->map(fn (Collection $group) => $group->pluck('content_id')->unique()->values()->all());

        $likes = $this->countsByType(Like::class, 'likeable_type', $keys);
        $shares = $this->countsByType(Share::class, 'shareable_type', $keys);

        return $items->map(function (array $item) use ($likes, $shares) {
            $key = $item['likeable_type'] . ':' . $item['content_id'];

            $item['likes_count'] = $likes[$key] ?? 0;
            $item['tells_count'] = $shares[$key] ?? 0;

            return $item;
        });
    }

    /**
     * Считает связанные действия по типам и идентификаторам сущностей.
     *
     * @param string $modelClass
     * @param string $typeColumn
     * @param Collection $keys
     * @return Collection
     */
    private function countsByType(string $modelClass, string $typeColumn, Collection $keys): Collection
    {
        if ($keys->isEmpty()) {
            return collect();
        }

        return $modelClass::query()
            ->selectRaw($typeColumn . ' as type, content_id, COUNT(*) as total')
            ->where(function ($query) use ($keys, $typeColumn) {
                foreach ($keys as $type => $ids) {
                    $query->orWhere(function ($query) use ($typeColumn, $type, $ids) {
                        $query->where($typeColumn, $type)
                            ->whereIn('content_id', $ids);
                    });
                }
            })
            ->groupBy($typeColumn, 'content_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->type . ':' . $row->content_id => (int) $row->total]);
    }

    /**
     * Возвращает отображаемое имя пользователя из строки выборки.
     */
    private function userName(object $row): string
    {
        $name = trim(sprintf('%s %s', (string) $row->firstname, (string) $row->lastname));

        return $name !== '' ? $name : (string) $row->email;
    }

    /**
     * Формирует HTML вложений комментария для ленты новостей.
     *
     * @param int $commentId
     * @return string
     */
    private function commentAttachmentsHtml(int $commentId): string
    {
        $photos = Attachment::query()
            ->from('attachment as a')
            ->join('photos as p', 'p.id', '=', 'a.photo_id')
            ->leftJoin('photoalbums as pa', 'pa.id', '=', 'p.photoalbum_id')
            ->select(['p.id', 'p.small_photo', 'p.photo', 'pa.photoalbumable_type'])
            ->where('a.type', 'comment')
            ->where('a.content_id', $commentId)
            ->orderBy('a.id')
            ->get();

        if ($photos->isEmpty()) {
            return '<ul class="attach_image"></ul>';
        }

        $items = $photos
            ->map(fn ($photo) => sprintf(
                '<li><img border="0" src="%s" class="photo_big" data-num="%d"></li>',
                e($this->formatter->photoUrl($photo->small_photo ?: $photo->photo, $photo->photoalbumable_type)),
                (int) $photo->id
            ))
            ->implode('');

        return '<ul class="attach_image">' . $items . '</ul>';
    }
}
