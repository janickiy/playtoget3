<?php

namespace App\Repositories;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\NewsRssSport;
use App\Models\Photo;
use App\Models\Share;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class NewsRepository extends BaseRepository
{
    public function __construct(NewsRssSport $model)
    {
        parent::__construct($model);
    }

    public function latest(int $limit = 20): Collection
    {
        return $this->feed($limit);
    }

    public function feed(int $limit = 25): Collection
    {
        return $this->feedPage($limit);
    }

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
                    e($this->photoUrl($row->small_photo ?: $row->photo, $row->photoalbumable_type)),
                    (int) $row->id
                ),
            ]));
    }

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
                    e($this->videoThumbUrl($row->provider, $row->video)),
                    (int) $row->id
                ),
            ]));
    }

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
                    . nl2br($this->safeText((string) $row->content))
                    . $this->commentAttachmentsHtml((int) $row->id),
            ]));
    }

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
                    nl2br($this->safeText((string) $row->content)),
                    e(route('front.profile.show', ['user' => (int) $row->owner_id])),
                    e($this->ownerAvatar($row)),
                    e($this->ownerName($row)),
                    e($this->russianDate(Carbon::parse($row->content_created_at ?: $row->created_at))),
                    e($this->photoUrl($row->small_photo ?: $row->photo, $row->photoalbumable_type)),
                    (int) $row->photo_id
                ),
            ]));
    }

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
                    nl2br($this->safeText((string) $row->content)),
                    e(route('front.profile.show', ['user' => (int) $row->owner_id])),
                    e($this->ownerAvatar($row)),
                    e($this->ownerName($row)),
                    e($this->russianDate(Carbon::parse($row->content_created_at ?: $row->created_at))),
                    e($this->videoThumbUrl($row->provider, $row->video)),
                    (int) $row->video_id
                ),
            ]));
    }

    private function makeUserItem(object $row, array $data): array
    {
        $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();

        return [
            'author_id' => (int) $row->author_id,
            'author_name' => $this->userName($row),
            'avatar' => $this->userAvatar($row),
            'date' => $this->russianDate($createdAt),
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

    private function userName(object $row): string
    {
        $name = trim(sprintf('%s %s', (string) $row->firstname, (string) $row->lastname));

        return $name !== '' ? $name : (string) $row->email;
    }

    private function userAvatar(object $row): string
    {
        if ((bool) $row->banned || (bool) $row->deleted) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->avatar && ($url = $this->publicImageUrl('user/avatar/' . $row->avatar))) {
            return $url;
        }

        return asset($row->sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    private function ownerName(object $row): string
    {
        $name = trim(sprintf('%s %s', (string) $row->owner_firstname, (string) $row->owner_lastname));

        return $name !== '' ? $name : (string) $row->owner_email;
    }

    private function ownerAvatar(object $row): string
    {
        if ((bool) $row->owner_banned || (bool) $row->owner_deleted) {
            return asset('frontend/images/noimage.png');
        }

        if ($row->owner_avatar && ($url = $this->publicImageUrl('user/avatar/' . $row->owner_avatar))) {
            return $url;
        }

        return asset($row->owner_sex === 'female' ? 'frontend/images/default_female.png' : 'frontend/images/default_male.png');
    }

    private function safeText(string $value): string
    {
        return e(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

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
                e($this->photoUrl($photo->small_photo ?: $photo->photo, $photo->photoalbumable_type)),
                (int) $photo->id
            ))
            ->implode('');

        return '<ul class="attach_image">' . $items . '</ul>';
    }

    private function photoUrl(?string $file, ?string $type): string
    {
        if (! $file) {
            return asset('frontend/images/noimage.png');
        }

        $type = $type ?: 'user';
        $paths = [
            "photogallery/{$type}/{$file}",
            "photogallery/user_attach/{$file}",
            "photogallery/user/{$file}",
        ];

        foreach ($paths as $path) {
            if ($url = $this->publicImageUrl($path)) {
                return $url;
            }
        }

        return asset('frontend/images/noimage.png');
    }

    private function publicImageUrl(string $path): ?string
    {
        $path = 'images/' . ltrim($path, '/');

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    private function videoThumbUrl(?string $provider, ?string $video): string
    {
        if ($provider === 'youtube' && $video) {
            return 'https://img.youtube.com/vi/' . rawurlencode($video) . '/hqdefault.jpg';
        }

        return asset('frontend/images/noimage.png');
    }

    private function russianDate(Carbon $date): string
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        return $date->day . ' ' . $months[$date->month] . ' ' . $date->year;
    }
}
