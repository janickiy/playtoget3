<?php

namespace App\Repositories;

use App\DTO\Profile\ProfileSettingsData;
use App\DTO\Profile\CommentData;
use App\DTO\Profile\ImageCropData;
use App\Enums\UserStatus;
use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\Friend;
use App\Models\Like;
use App\Models\Log;
use App\Models\Share;
use App\Models\User;
use App\Models\UserSetting;
use App\Service\ProfileImageService;
use App\Support\MediaPath;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfileRepository extends BaseRepository
{
    private const MONTHS = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    private const CONTACT_FIELDS = [
        'contact_email',
        'phone',
        'telegram',
        'whatsapp',
        'viber',
        'website',
    ];

    private const PERMISSION_FIELDS = [
        'permission_send_message' => 'Who can message me',
        'permission_view_profile' => 'Who can view my profile',
        'permission_view_friends' => 'Who can see my friends list',
        'permission_view_photo' => 'Who can view my photos',
        'permission_view_video' => 'Who can view my videos',
        'permission_view_wall' => 'Who can view posts on my wall',
        'permission_comment_photo' => 'Who can comment on my photos',
        'permission_comment_video' => 'Who can comment on my videos',
        'permission_comment_wall' => 'Who can comment on posts on my wall',
    ];

    private const NOTIFICATION_FIELDS = [
        'notification_friends_request' => 'Friend requests',
        'notification_private_messages' => 'Private messages',
        'notification_wall_comments' => 'Wall comments',
        'notification_picture_comments' => 'Photo comments',
        'notification_video_comments' => 'Video comments',
        'notification_answers_in_comments' => 'Replies in comments',
        'notification_events' => 'Events',
        'notification_birthdays' => 'Birthdays',
    ];

    /**
     * Connects модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(
        User $model,
        private readonly ProfileImageService $images
    )
    {
        parent::__construct($model);
    }

    /**
     * Finds профиль user для view.
     */
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
            ->whereIn('status', [
                UserStatus::Confirmed->value,
                UserStatus::Blocked->value,
                UserStatus::Deleted->value,
            ])
            ->first();

        return $user;
    }

    /**
     * Builds основные data профиля для page user.
     */
    public function profileData(User $profile): array
    {
        return [
            'avatar' => FrontAssets::userAvatar($profile),
            'cover' => FrontAssets::userCover($profile),
            'firstname' => $profile->firstname ?: $profile->displayName(),
            'lastname' => (string)$profile->lastname,
            'secondname' => (string)$profile->secondname,
            'about' => (string)$profile->about,
            'last_visit' => $this->dateTime($profile->activity?->last_activity),
            'birthday' => $this->date($profile->birthday),
            'city' => (string)$profile->city,
            'phone' => (string)$profile->phone,
            'contact_email' => (string)$profile->contact_email,
            'telegram' => (string)$profile->telegram,
            'whatsapp' => (string)$profile->whatsapp,
            'viber' => (string)$profile->viber,
            'website' => (string)$profile->website,
            'about_sport' => (string)$profile->about_sport,
            'is_online' => $profile->isOnline(),
            'sport_types' => $profile->sportTypes
                ->map(fn($row): array => [
                    'sport_type' => (string)($row->sportType?->name ?? ''),
                    'sport_level' => (string)($row->sportLevel?->name ?? ''),
                    'search_team' => (int)$row->search_team === 1 ? 'yes' : 'no',
                ])
                ->filter(fn(array $row): bool => $row['sport_type'] !== '' || $row['sport_level'] !== '')
                ->values(),
            'education' => $this->occupations($profile, 1),
            'work' => $this->occupations($profile, 3),
        ];
    }

    /**
     * Builds data верхнего block профиля user.
     */
    public function topProfileData(User $user): array
    {
        return [
            'user' => $user,
            'avatar' => FrontAssets::userAvatar($user),
            'cover' => FrontAssets::userCover($user),
            'firstname' => $user->firstname ?: $user->displayName(),
            'lastname' => (string)$user->lastname,
            'about' => (string)$user->about,
        ];
    }

    /**
     * Returns настройки профиля or creates их со значениями по умолчанию.
     */
    public function profileSettings(User $user): UserSetting
    {
        /** @var UserSetting $settings */
        $settings = $user->settings ?: UserSetting::query()->firstOrNew(['user_id' => $user->id]);

        foreach (array_keys(self::PERMISSION_FIELDS) as $field) {
            $settings->{$field} ??= 0;
        }

        foreach (array_keys(self::NOTIFICATION_FIELDS) as $field) {
            $settings->{$field} ??= 'yes';
        }

        return $settings;
    }

    /**
     * Returns users, blocked текущим профилем.
     */
    public function blockedUsers(User $user): Collection
    {
        return Friend::query()
            ->where('user_id', $user->id)
            ->where('status', 2)
            ->with('friend')
            ->orderByDesc('added')
            ->get()
            ->map(fn(Friend $relation): ?array => $relation->friend
                ? [
                    'id' => (int)$relation->friend->id,
                    'name' => $relation->friend->displayName(),
                    'avatar' => FrontAssets::userAvatar($relation->friend),
                    'url' => route('front.profile.show', ['user' => $relation->friend->id]),
                ]
                : null)
            ->filter()
            ->values();
    }

    /**
     * Returns последние записи журнала безопасности user.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function securityLogs(User $user, int $limit = 10): Collection
    {
        return Log::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_sign_in_at')
            ->limit($limit)
            ->get()
            ->map(fn(Log $log): array => [
                'ip' => (string)$log->ip,
                'os' => $this->detectOs((string)$log->user_agent),
                'browser' => $this->detectBrowser((string)$log->user_agent),
                'time' => $log->last_sign_in_at?->format('d.m.Y H:i') ?? '',
            ]);
    }

    /**
     * Returns list полей прав accessа профиля.
     */
    public function permissionFields(): array
    {
        return self::PERMISSION_FIELDS;
    }

    /**
     * Returns list полей уведомлений профиля.
     */
    public function notificationFields(): array
    {
        return self::NOTIFICATION_FIELDS;
    }

    /**
     * Обрезает uploaded avatar во temporary квадратный файл.
     */
    public function cropTemporaryAvatar(User $user, ImageCropData $data): array
    {
        return $this->images->cropTemporaryAvatar($user, $data);
    }

    /**
     * Updates профиль, настройки приватности и image user.
     *
     * @param User $user
     * @param ProfileSettingsData $data
     * @return void
     * @throws \Throwable
     */
    public function updateProfileSettings(User $user, ProfileSettingsData $data): void
    {
        $contacts = [];
        foreach (self::CONTACT_FIELDS as $field) {
            $contacts[$field] = trim((string)($data->user[$field] ?? ''));
        }

        $permissions = [];
        foreach (array_keys(self::PERMISSION_FIELDS) as $field) {
            $permissions[$field] = (int)($data->user[$field] ?? 0);
        }
        foreach (['permission_send_message', 'permission_view_profile'] as $field) {
            if (($permissions[$field] ?? 0) > 1) {
                $permissions[$field] = 1;
            }
        }

        $notifications = [];
        foreach (array_keys(self::NOTIFICATION_FIELDS) as $field) {
            $notifications[$field] = array_key_exists($field, $data->user) ? 'yes' : 'no';
        }

        $newAvatar = $this->images->promoteTemporaryAvatar($data->temporaryAvatar, $user->id);
        $newCover = $this->images->promoteTemporaryCover($data->temporaryCover, $user->id)
            ?? ($data->coverFile ? $this->images->storeUserImage($data->coverFile, MediaPath::directory('user_cover'), $user->id) : null);
        $oldAvatar = null;
        $oldCover = null;

        try {
            DB::transaction(function () use ($user, $contacts, $permissions, $notifications, $newAvatar, $newCover, &$oldAvatar, &$oldCover): void {
                $user->fill($contacts);

                if ($newAvatar) {
                    $oldAvatar = (string)$user->avatar;
                    $user->avatar = $newAvatar;
                }

                if ($newCover) {
                    $oldCover = (string)$user->cover_page;
                    $user->cover_page = $newCover;
                }

                $user->save();

                /** @var UserSetting $settings */
                $settings = UserSetting::query()->firstOrNew(['user_id' => $user->id]);
                $settings->fill($permissions + $notifications + ['user_id' => $user->id]);
                $settings->save();
            });
        } catch (\Throwable $exception) {
            $this->images->deleteUserImage(MediaPath::directory('user_avatar'), $newAvatar);
            $this->images->deleteUserImage(MediaPath::directory('user_cover'), $newCover);

            throw $exception;
        }

        $this->images->deleteUserImage(MediaPath::directory('user_avatar'), $oldAvatar);
        $this->images->deleteUserImage(MediaPath::directory('user_cover'), $oldCover);
    }

    /**
     * Returns набор прав user для current entity.
     *
     * @param User $profile
     * @param User|null $viewer
     * @param string $friendshipStatus
     * @return array|false[]
     */
    public function permissions(User $profile, ?User $viewer, string $friendshipStatus): array
    {
        $isOwnPage = $viewer && (int)$viewer->id === (int)$profile->id;
        $isFriend = $friendshipStatus === 'friend';
        $blockedByProfile = $friendshipStatus === 'blocked_by_user';

        if (! $profile->isActive() || $blockedByProfile) {
            return [
                'profile' => false,
                'send_message' => false,
                'wall' => false,
                'photo' => false,
                'video' => false,
                'friends' => false,
                'teams' => false,
                'blocked_by_profile' => $blockedByProfile,
            ];
        }

        $settings = $profile->settings;
        $canViewProfile = $this->limitedPermissionAllows($settings?->permission_view_profile, $isOwnPage, $isFriend);

        return [
            'profile' => $canViewProfile,
            'send_message' => $viewer
                && !$isOwnPage
                && $this->limitedPermissionAllows($settings?->permission_send_message, $isOwnPage, $isFriend),
            'wall' => $canViewProfile && $this->permissionAllows($settings?->permission_view_wall, $isOwnPage, $isFriend),
            'photo' => $canViewProfile && $this->permissionAllows($settings?->permission_view_photo, $isOwnPage, $isFriend),
            'video' => $canViewProfile && $this->permissionAllows($settings?->permission_view_video, $isOwnPage, $isFriend),
            'friends' => $canViewProfile && $this->permissionAllows($settings?->permission_view_friends, $isOwnPage, $isFriend),
            'teams' => $canViewProfile
                && (bool)$viewer
                && $profile->communities()
                    ->where('communities.type', 'team')
                    ->exists(),
            'blocked_by_profile' => false,
        ];
    }

    /**
     * Returns комментарии стены профиля.
     *
     * @param int $profileId
     * @param int $limit
     * @param int $offset
     * @param User|null $viewer
     * @return Collection
     */
    public function wallComments(int $profileId, int $limit = 10, int $offset = 0, ?User $viewer = null): Collection
    {
        return $this->comments('user', $profileId, $limit, $offset, $viewer);
    }

    /**
     * Returns комментарии entity с древовидными ответами.
     */
    public function comments(
        string $commentableType,
        int    $contentId,
        int    $limit = 10,
        int    $offset = 0,
        ?User  $viewer = null,
    ): Collection
    {
        return $this->commentsQuery($commentableType, $contentId)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn(Comment $comment): array => $this->serializeComment($comment, $viewer));
    }

    /**
     * Checks, есть ли еще комментарии стены после current page.
     *
     * @param int $profileId
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreWallComments(int $profileId, int $limit = 10, int $offset = 0): bool
    {
        return $this->hasMoreComments('user', $profileId, $limit, $offset);
    }

    /**
     * Checks, есть ли еще комментарии у entity после current page.
     *
     * @param string $commentableType
     * @param int $contentId
     * @param int $limit
     * @param int $offset
     * @return bool
     */
    public function hasMoreComments(string $commentableType, int $contentId, int $limit = 10, int $offset = 0): bool
    {
        return $this->commentsQuery($commentableType, $contentId)
            ->offset($offset + $limit)
            ->limit(1)
            ->exists();
    }

    /**
     * Creates комментарий на стене or у связанной entity.
     *
     * @param User $author
     * @param CommentData $data
     * @return Comment
     */
    public function createWallComment(User $author, CommentData $data): Comment
    {
        /** @var Comment $comment */
        $comment = Comment::query()->create([
            'commentable_type' => $data->commentableType,
            'content_id' => $data->contentId,
            'user_id' => $author->id,
            'behalfable_type' => $data->behalfableType,
            'behalf_id' => $data->behalfId,
            'content' => $data->content,
            'parent_id' => $data->parentId,
        ]);

        foreach ($this->attachmentIds($data->attach) as $photoId) {
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

    /**
     * Deletes комментарий, если у user есть permissions.
     *
     * @param User $viewer
     * @param int $commentId
     * @return bool
     * @throws \Throwable
     */
    public function deleteComment(User $viewer, int $commentId): bool
    {
        /** @var Comment|null $comment */
        $comment = Comment::query()->whereKey($commentId)->first();

        if (!$comment || !$this->viewerCanDeleteComment($viewer, $comment)) {
            return false;
        }

        $commentIds = $this->commentTreeIds($comment);

        DB::transaction(function () use ($commentIds): void {
            Attachment::query()
                ->where('type', 'comment')
                ->whereIn('content_id', $commentIds)
                ->delete();

            Like::query()
                ->where('likeable_type', 'comment')
                ->whereIn('content_id', $commentIds)
                ->delete();

            Share::query()
                ->where('shareable_type', 'comment')
                ->whereIn('content_id', $commentIds)
                ->delete();

            Comment::query()
                ->whereIn('id', $commentIds)
                ->orderByDesc('id')
                ->get()
                ->each
                ->delete();
        });

        return true;
    }

    /**
     * Преобразует комментарий и ответы в массив data для output.
     *
     * @param Comment $comment
     * @param User|null $viewer
     * @param bool $includeReplies
     * @return array
     */
    public function serializeComment(Comment $comment, ?User $viewer = null, bool $includeReplies = true): array
    {
        $user = $comment->user;
        $behalf = in_array((string) $comment->behalfable_type, ['team', 'group'], true) && (int) $comment->behalf_id > 0
            ? Community::query()->find((int) $comment->behalf_id)
            : null;
        $authorName = $user?->displayName() ?? '';
        $authorUrl = $user ? route('front.profile.show', ['user' => $user->id]) : route('front.news.index');
        $avatar = FrontAssets::userAvatar($user);

        if ($behalf instanceof Community) {
            $authorName = (string) $behalf->name;
            $authorUrl = $behalf->type === 'group'
                ? route('front.groups.show', ['community' => $behalf->id])
                : route('front.teams.show', ['community' => $behalf->id]);
            $avatar = FrontAssets::communityAvatar($behalf);
        }

        return [
            'id' => (int)$comment->id,
            'parent_id' => (int)$comment->parent_id,
            'content_id' => (int)$comment->content_id,
            'author_id' => (int)$comment->user_id,
            'author_name' => $authorName,
            'author_url' => $authorUrl,
            'avatar' => $avatar,
            'created' => $comment->created_at?->format('d.m.Y H:i') ?? '',
            'content' => (string)$comment->content,
            'attachments' => $comment->attachments
                ->map(fn(Attachment $attachment): ?array => $this->serializeAttachment($attachment))
                ->filter()
                ->values(),
            'likes_count' => (int)($comment->likes_count ?? 0),
            'shares_count' => (int)($comment->shares_count ?? 0),
            'can_interact' => (bool)$viewer,
            'can_share' => $viewer && (int)$viewer->id !== (int)$comment->user_id,
            'can_delete' => $viewer && $this->viewerCanDeleteComment($viewer, $comment),
            'replies' => $includeReplies
                ? $comment->replies->map(fn(Comment $reply): array => $this->serializeComment($reply, $viewer, false))->values()
                : collect(),
        ];
    }

    /**
     * Готовит базовый запрос комментариев для указанной entity.
     *
     * @param string $commentableType
     * @param int $contentId
     * @return Builder
     */
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
                'replies' => fn($query) => $query
                    ->with(['user', 'attachments.photo.album'])
                    ->withCount(['likes', 'shares'])
                    ->orderBy('id'),
            ])
            ->withCount(['likes', 'shares'])
            ->orderByDesc('id');
    }

    /**
     * Checks, может ли user delete комментарий.
     *
     * @param User $viewer
     * @param Comment $comment
     * @return bool
     */
    private function viewerCanDeleteComment(User $viewer, Comment $comment): bool
    {
        if ((int)$viewer->id === (int)$comment->user_id) {
            return true;
        }

        if ((string)$comment->commentable_type === 'user') {
            return (int)$viewer->id === (int)$comment->content_id;
        }

        if (in_array((string)$comment->commentable_type, ['team', 'group'], true)) {
            return CommunityRole::query()
                ->where('community_id', (int)$comment->content_id)
                ->where('user_id', (int)$viewer->id)
                ->whereIn('role', [1, 2])
                ->exists();
        }

        if ((string)$comment->commentable_type === 'event') {
            return AcceptedEventMember::query()
                ->where('event_id', (int)$comment->content_id)
                ->where('eventable_type', 'user')
                ->where('member_id', (int)$viewer->id)
                ->whereIn('role', [1, 2])
                ->exists();
        }

        return false;
    }

    /**
     * Собирает идентификаторы комментария и всех его дочерних ответов.
     *
     * @param Comment $comment
     * @return Collection
     */
    private function commentTreeIds(Comment $comment): Collection
    {
        $ids = collect([(int)$comment->id]);
        $currentIds = [(int)$comment->id];

        while ($currentIds !== []) {
            $childIds = Comment::query()
                ->whereIn('parent_id', $currentIds)
                ->pluck('id')
                ->map(fn(int|string $id): int => (int)$id)
                ->all();

            $childIds = array_values(array_diff($childIds, $ids->all()));

            if ($childIds === []) {
                break;
            }

            $ids = $ids->merge($childIds);
            $currentIds = $childIds;
        }

        return $ids->unique()->values();
    }

    /**
     * Returns занятия user указанного typeа.
     *
     * @param User $profile
     * @param int $kind
     * @return Collection
     */
    private function occupations(User $profile, int $kind): Collection
    {
        return $profile->occupations
            ->where('kind', $kind)
            ->map(fn($row): array => [
                'name' => (string)$row->name,
                'description' => (string)$row->description,
                'period' => trim(implode(' - ', array_filter([
                    trim((string)$row->month_start . ' ' . (string)$row->year_start),
                    trim((string)$row->month_finish . ' ' . (string)$row->year_finish),
                ]))),
            ])
            ->filter(fn(array $row): bool => $row['name'] !== '')
            ->values();
    }

    /**
     * Преобразует вложение комментария в массив data для output.
     *
     * @param Attachment $attachment
     * @return array|null
     */
    private function serializeAttachment(Attachment $attachment): ?array
    {
        if (!$attachment->photo) {
            return null;
        }

        $url = FrontAssets::photoGallery($attachment->photo);

        if (!$url) {
            return null;
        }

        return [
            'photo_id' => (int)$attachment->photo->id,
            'url' => $url,
        ];
    }

    /**
     * Checks, разрешает ли настройка приватности selected действие.
     *
     * @param mixed $permission
     * @param bool $isOwnPage
     * @param bool $isFriend
     * @return bool
     */
    private function permissionAllows(mixed $permission, bool $isOwnPage, bool $isFriend): bool
    {
        return match ((int)($permission ?? 0)) {
            1 => $isOwnPage || $isFriend,
            2 => $isOwnPage,
            default => true,
        };
    }

    /**
     * Checks настройки с accessными значениями «Everyone» и «Friends».
     *
     * @param mixed $permission
     * @param bool $isOwnPage
     * @param bool $isFriend
     * @return bool
     */
    private function limitedPermissionAllows(mixed $permission, bool $isOwnPage, bool $isFriend): bool
    {
        return match ((int)($permission ?? 0)) {
            1, 2 => $isOwnPage || $isFriend,
            default => true,
        };
    }

    /**
     * Нормализует list идентификаторов вложений из входных data.
     *
     * @param mixed $attach
     * @return array
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
     * Detects операционную систему по строке User-Agent.
     */
    private function detectOs(string $userAgent): string
    {
        return match (true) {
            stripos($userAgent, 'Windows') !== false => 'Windows',
            stripos($userAgent, 'Mac OS') !== false || stripos($userAgent, 'Macintosh') !== false => 'macOS',
            stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false => 'iOS',
            stripos($userAgent, 'Android') !== false => 'Android',
            stripos($userAgent, 'Linux') !== false => 'Linux',
            default => 'Not detected',
        };
    }

    /**
     * Detects браузер по строке User-Agent.
     *
     * @param string $userAgent
     * @return string
     */
    private function detectBrowser(string $userAgent): string
    {
        return match (true) {
            stripos($userAgent, 'Edg') !== false => 'Edge',
            stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false => 'Opera',
            stripos($userAgent, 'Firefox') !== false => 'Firefox',
            stripos($userAgent, 'Chrome') !== false => 'Chrome',
            stripos($userAgent, 'Safari') !== false => 'Safari',
            default => 'Not detected',
        };
    }

    /**
     * Форматирует date и время для output в интерфейсе.
     *
     * @param CarbonInterface|null $date
     * @return string
     */
    private function dateTime(?CarbonInterface $date): string
    {
        return $date
            ? sprintf('%d %s %d at %02d:%02d', $date->day, self::MONTHS[$date->month], $date->year, $date->hour, $date->month)
            : '';
    }

    /**
     * Форматирует date для output в интерфейсе.
     *
     * @param CarbonInterface|null $date
     * @return string
     */
    private function date(?CarbonInterface $date): string
    {
        return $date
            ? sprintf('%d %s %d', $date->day, self::MONTHS[$date->month], $date->year)
            : '';
    }
}
