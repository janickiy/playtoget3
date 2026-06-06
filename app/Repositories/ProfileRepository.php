<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProfileRepository extends BaseRepository
{
    private const MONTHS = [
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

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function profile(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->with([
                'settings',
                'activity',
                'occupations',
                'sportTypes.sportType',
                'sportTypes.sportLevel',
            ])
            ->whereKey($id)
            ->where('confirmed', true)
            ->first();

        return $user;
    }

    public function profileData(User $profile): array
    {
        return [
            'avatar' => FrontAssets::userAvatar($profile),
            'cover' => FrontAssets::userCover($profile),
            'firstname' => $profile->firstname ?: $profile->displayName(),
            'lastname' => (string) $profile->lastname,
            'secondname' => (string) $profile->secondname,
            'about' => (string) $profile->about,
            'last_visit' => $this->dateTime($profile->activity?->last_activity),
            'birthday' => $this->date($profile->birthday),
            'city' => (string) $profile->city,
            'phone' => (string) $profile->phone,
            'contact_email' => (string) $profile->contact_email,
            'skype' => (string) $profile->skype,
            'website' => (string) $profile->website,
            'about_sport' => (string) $profile->about_sport,
            'is_online' => $profile->activity?->last_activity
                ? $profile->activity->last_activity->greaterThan(now()->subMinutes(5))
                : false,
            'sport_types' => $profile->sportTypes
                ->map(fn ($row): array => [
                    'sport_type' => (string) ($row->sportType?->name ?? ''),
                    'sport_level' => (string) ($row->sportLevel?->name ?? ''),
                    'search_team' => (int) $row->search_team === 1 ? 'да' : 'нет',
                ])
                ->filter(fn (array $row): bool => $row['sport_type'] !== '' || $row['sport_level'] !== '')
                ->values(),
            'education' => $this->occupations($profile, 1),
            'work' => $this->occupations($profile, 3),
        ];
    }

    public function permissions(User $profile, ?User $viewer, string $friendshipStatus): array
    {
        $isOwnPage = $viewer && (int) $viewer->id === (int) $profile->id;
        $isFriend = $friendshipStatus === 'friend';

        if ((bool) $profile->banned || (bool) $profile->deleted) {
            return [
                'send_message' => false,
                'wall' => false,
                'photo' => false,
                'video' => false,
                'friends' => false,
                'teams' => false,
            ];
        }

        $settings = $profile->settings;

        return [
            'send_message' => $viewer
                && ! $isOwnPage
                && $this->permissionAllows($settings?->permission_send_message, $isOwnPage, $isFriend),
            'wall' => $this->permissionAllows($settings?->permission_view_wall, $isOwnPage, $isFriend),
            'photo' => $this->permissionAllows($settings?->permission_view_photo, $isOwnPage, $isFriend),
            'video' => $this->permissionAllows($settings?->permission_view_video, $isOwnPage, $isFriend),
            'friends' => $this->permissionAllows($settings?->permission_view_friends, $isOwnPage, $isFriend),
            'teams' => (bool) $viewer
                && $profile->communities()
                    ->where('communities.type', 'team')
                    ->exists(),
        ];
    }

    public function wallComments(int $profileId, int $limit = 10, int $offset = 0, ?User $viewer = null): Collection
    {
        return $this->comments('user', $profileId, $limit, $offset, $viewer);
    }

    public function comments(
        string $commentableType,
        int $contentId,
        int $limit = 10,
        int $offset = 0,
        ?User $viewer = null,
    ): Collection {
        return $this->commentsQuery($commentableType, $contentId)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Comment $comment): array => $this->serializeComment($comment, $viewer));
    }

    public function hasMoreWallComments(int $profileId, int $limit = 10, int $offset = 0): bool
    {
        return $this->hasMoreComments('user', $profileId, $limit, $offset);
    }

    public function hasMoreComments(string $commentableType, int $contentId, int $limit = 10, int $offset = 0): bool
    {
        return $this->commentsQuery($commentableType, $contentId)
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    public function createWallComment(User $author, array $data): Comment
    {
        /** @var Comment $comment */
        $comment = Comment::query()->create([
            'commentable_type' => (string) $data['commentable_type'],
            'content_id' => (int) $data['content_id'],
            'user_id' => $author->id,
            'behalfable_type' => (string) ($data['behalfable_type'] ?? ''),
            'behalf_id' => (int) ($data['behalf_id'] ?? 0),
            'content' => (string) ($data['comment'] ?? ''),
            'parent_id' => (int) ($data['parent_id'] ?? 0),
        ]);

        foreach ($this->attachmentIds($data['attach'] ?? []) as $photoId) {
            Attachment::query()->create([
                'type' => 'comment',
                'content_id' => $comment->id,
                'photo_id' => $photoId,
            ]);
        }

        return $comment->load([
            'user',
            'attachments.photo.album',
            'replies.user',
            'replies.attachments.photo.album',
        ])->loadCount(['likes', 'shares']);
    }

    public function serializeComment(Comment $comment, ?User $viewer = null, bool $includeReplies = true): array
    {
        $user = $comment->user;

        return [
            'id' => (int) $comment->id,
            'parent_id' => (int) $comment->parent_id,
            'content_id' => (int) $comment->content_id,
            'author_id' => (int) $comment->user_id,
            'author_name' => $user?->displayName() ?? '',
            'author_url' => $user ? route('front.profile.show', ['user' => $user->id]) : route('front.news.index'),
            'avatar' => FrontAssets::userAvatar($user),
            'created' => $comment->created_at?->format('d.m.Y H:i') ?? '',
            'content' => (string) $comment->content,
            'attachments' => $comment->attachments
                ->map(fn (Attachment $attachment): ?array => $this->serializeAttachment($attachment))
                ->filter()
                ->values(),
            'likes_count' => (int) ($comment->likes_count ?? 0),
            'shares_count' => (int) ($comment->shares_count ?? 0),
            'can_interact' => (bool) $viewer,
            'can_share' => $viewer && (int) $viewer->id !== (int) $comment->user_id,
            'can_delete' => $viewer && (
                (int) $viewer->id === (int) $comment->user_id
                || (int) $viewer->id === (int) $comment->content_id
            ),
            'replies' => $includeReplies
                ? $comment->replies->map(fn (Comment $reply): array => $this->serializeComment($reply, $viewer, false))->values()
                : collect(),
        ];
    }

    private function commentsQuery(string $commentableType, int $contentId): Builder
    {
        return Comment::query()
            ->where('commentable_type', $commentableType)
            ->where('content_id', $contentId)
            ->where(function (Builder $query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->with([
                'user',
                'attachments.photo.album',
                'replies' => fn ($query) => $query
                    ->with(['user', 'attachments.photo.album'])
                    ->withCount(['likes', 'shares'])
                    ->orderBy('id'),
            ])
            ->withCount(['likes', 'shares'])
            ->orderByDesc('id');
    }

    private function occupations(User $profile, int $kind): Collection
    {
        return $profile->occupations
            ->where('kind', $kind)
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'description' => (string) $row->description,
                'period' => trim(implode(' - ', array_filter([
                    trim((string) $row->month_start . ' ' . (string) $row->year_start),
                    trim((string) $row->month_finish . ' ' . (string) $row->year_finish),
                ]))),
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->values();
    }

    private function serializeAttachment(Attachment $attachment): ?array
    {
        if (! $attachment->photo) {
            return null;
        }

        $url = FrontAssets::photoGallery($attachment->photo);

        if (! $url) {
            return null;
        }

        return [
            'photo_id' => (int) $attachment->photo->id,
            'url' => $url,
        ];
    }

    private function permissionAllows(mixed $permission, bool $isOwnPage, bool $isFriend): bool
    {
        return match ((int) ($permission ?? 0)) {
            1 => $isOwnPage || $isFriend,
            2 => $isOwnPage,
            default => true,
        };
    }

    private function attachmentIds(mixed $attach): array
    {
        if (is_string($attach)) {
            $attach = explode(',', $attach);
        }

        if (! is_array($attach)) {
            return [];
        }

        return collect($attach)
            ->flatMap(fn ($value): array => is_array($value) ? $value : [$value])
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function dateTime(?CarbonInterface $date): string
    {
        return $date
            ? sprintf('%d %s %d в %02d:%02d', $date->day, self::MONTHS[$date->month], $date->year, $date->hour, $date->month)
            : '';
    }

    private function date(?CarbonInterface $date): string
    {
        return $date
            ? sprintf('%d %s %d', $date->day, self::MONTHS[$date->month], $date->year)
            : '';
    }
}
