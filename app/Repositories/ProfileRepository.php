<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Friend;
use App\Models\Like;
use App\Models\Log;
use App\Models\Share;
use App\Models\User;
use App\Models\UserSetting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

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

    private const CONTACT_FIELDS = [
        'contact_email',
        'phone',
        'skype',
        'website',
    ];

    private const PERMISSION_FIELDS = [
        'permission_send_message' => 'Кто может писать мне сообщения',
        'permission_view_profile' => 'Кто может просматривать мой профиль',
        'permission_view_friends' => 'Кто может видеть список моих друзей',
        'permission_view_photo' => 'Кто может просматривать мои фотографии',
        'permission_view_video' => 'Кто может просматривать мои видеозаписи',
        'permission_view_wall' => 'Кто может просматривать записи на моей стене',
        'permission_comment_photo' => 'Кто может комментировать мои фотографии',
        'permission_comment_video' => 'Кто может комментировать мои видеозаписи',
        'permission_comment_wall' => 'Кто может комментировать записи на моей стене',
    ];

    private const NOTIFICATION_FIELDS = [
        'notification_friends_request' => 'Заявки в друзья',
        'notification_private_messages' => 'Личные сообщения',
        'notification_wall_comments' => 'Комментарии на стене',
        'notification_picture_comments' => 'Комментарии к фотографиям',
        'notification_video_comments' => 'Комментарии к видео',
        'notification_answers_in_comments' => 'Ответы в комментариях',
        'notification_events' => 'Мероприятия',
        'notification_birthdays' => 'Дни рождения',
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
            'lastname' => (string)$profile->lastname,
            'secondname' => (string)$profile->secondname,
            'about' => (string)$profile->about,
            'last_visit' => $this->dateTime($profile->activity?->last_activity),
            'birthday' => $this->date($profile->birthday),
            'city' => (string)$profile->city,
            'phone' => (string)$profile->phone,
            'contact_email' => (string)$profile->contact_email,
            'skype' => (string)$profile->skype,
            'website' => (string)$profile->website,
            'about_sport' => (string)$profile->about_sport,
            'is_online' => $profile->activity?->last_activity
                ? $profile->activity->last_activity->greaterThan(now()->subMinutes(5))
                : false,
            'sport_types' => $profile->sportTypes
                ->map(fn($row): array => [
                    'sport_type' => (string)($row->sportType?->name ?? ''),
                    'sport_level' => (string)($row->sportLevel?->name ?? ''),
                    'search_team' => (int)$row->search_team === 1 ? 'да' : 'нет',
                ])
                ->filter(fn(array $row): bool => $row['sport_type'] !== '' || $row['sport_level'] !== '')
                ->values(),
            'education' => $this->occupations($profile, 1),
            'work' => $this->occupations($profile, 3),
        ];
    }

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

    public function permissionFields(): array
    {
        return self::PERMISSION_FIELDS;
    }

    public function notificationFields(): array
    {
        return self::NOTIFICATION_FIELDS;
    }

    public function cropTemporaryAvatar(User $user, UploadedFile $file, array $crop): array
    {
        $source = $this->imageResource($file);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);

            throw new RuntimeException('Не удалось прочитать изображение.');
        }

        $x = max(0, (int)floor((float)($crop['x'] ?? 0)));
        $y = max(0, (int)floor((float)($crop['y'] ?? 0)));
        $width = max(0, (int)floor((float)($crop['w'] ?? 0)));
        $height = max(0, (int)floor((float)($crop['h'] ?? 0)));
        $size = min($width, $height);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область слишком мала.');
        }

        $size = min($size, $sourceWidth - $x, $sourceHeight - $y);

        if ($size < 100) {
            imagedestroy($source);

            throw new RuntimeException('Выделенная область выходит за границы изображения.');
        }

        $target = imagecreatetruecolor(300, 300);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, $x, $y, 300, 300, $size, $size);
        imagedestroy($source);

        ob_start();
        imagejpeg($target, null, 90);
        $contents = ob_get_clean();
        imagedestroy($target);

        if (!is_string($contents) || $contents === '') {
            throw new RuntimeException('Не удалось обработать изображение.');
        }

        $filename = sprintf('%d_%s.jpg', $user->id, Str::lower(Str::random(32)));
        $path = 'images/tmp/profile/avatar/' . $filename;

        if (!Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение.');
        }

        return [
            'file' => $filename,
            'url' => Storage::disk('public')->url($path),
        ];
    }

    public function updateProfileSettings(User $user, array $input, ?string $temporaryAvatar, ?UploadedFile $cover): void
    {
        $contacts = [];
        foreach (self::CONTACT_FIELDS as $field) {
            $contacts[$field] = trim((string)($input[$field] ?? ''));
        }

        $permissions = [];
        foreach (array_keys(self::PERMISSION_FIELDS) as $field) {
            $permissions[$field] = (int)($input[$field] ?? 0);
        }

        $notifications = [];
        foreach (array_keys(self::NOTIFICATION_FIELDS) as $field) {
            $notifications[$field] = array_key_exists($field, $input) ? 'yes' : 'no';
        }

        $newAvatar = $this->promoteTemporaryAvatar($temporaryAvatar, $user->id);
        $newCover = $cover ? $this->storeUserImage($cover, 'user/cover_page', $user->id) : null;
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
            $this->deleteUserImage('user/avatar', $newAvatar);
            $this->deleteUserImage('user/cover_page', $newCover);

            throw $exception;
        }

        $this->deleteUserImage('user/avatar', $oldAvatar);
        $this->deleteUserImage('user/cover_page', $oldCover);
    }

    public function permissions(User $profile, ?User $viewer, string $friendshipStatus): array
    {
        $isOwnPage = $viewer && (int)$viewer->id === (int)$profile->id;
        $isFriend = $friendshipStatus === 'friend';

        if ((bool)$profile->banned || (bool)$profile->deleted) {
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
                && !$isOwnPage
                && $this->permissionAllows($settings?->permission_send_message, $isOwnPage, $isFriend),
            'wall' => $this->permissionAllows($settings?->permission_view_wall, $isOwnPage, $isFriend),
            'photo' => $this->permissionAllows($settings?->permission_view_photo, $isOwnPage, $isFriend),
            'video' => $this->permissionAllows($settings?->permission_view_video, $isOwnPage, $isFriend),
            'friends' => $this->permissionAllows($settings?->permission_view_friends, $isOwnPage, $isFriend),
            'teams' => (bool)$viewer
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
            'commentable_type' => (string)$data['commentable_type'],
            'content_id' => (int)$data['content_id'],
            'user_id' => $author->id,
            'behalfable_type' => (string)($data['behalfable_type'] ?? ''),
            'behalf_id' => (int)($data['behalf_id'] ?? 0),
            'content' => (string)($data['comment'] ?? ''),
            'parent_id' => (int)($data['parent_id'] ?? 0),
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

    public function serializeComment(Comment $comment, ?User $viewer = null, bool $includeReplies = true): array
    {
        $user = $comment->user;

        return [
            'id' => (int)$comment->id,
            'parent_id' => (int)$comment->parent_id,
            'content_id' => (int)$comment->content_id,
            'author_id' => (int)$comment->user_id,
            'author_name' => $user?->displayName() ?? '',
            'author_url' => $user ? route('front.profile.show', ['user' => $user->id]) : route('front.news.index'),
            'avatar' => FrontAssets::userAvatar($user),
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
            'can_delete' => $viewer && (
                    (int)$viewer->id === (int)$comment->user_id
                    || (
                        (string)$comment->commentable_type === 'user'
                        && (int)$viewer->id === (int)$comment->content_id
                    )
                ),
            'replies' => $includeReplies
                ? $comment->replies->map(fn(Comment $reply): array => $this->serializeComment($reply, $viewer, false))->values()
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
                'replies' => fn($query) => $query
                    ->with(['user', 'attachments.photo.album'])
                    ->withCount(['likes', 'shares'])
                    ->orderBy('id'),
            ])
            ->withCount(['likes', 'shares'])
            ->orderByDesc('id');
    }

    private function viewerCanDeleteComment(User $viewer, Comment $comment): bool
    {
        if ((int)$viewer->id === (int)$comment->user_id) {
            return true;
        }

        return (string)$comment->commentable_type === 'user'
            && (int)$viewer->id === (int)$comment->content_id;
    }

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

    private function permissionAllows(mixed $permission, bool $isOwnPage, bool $isFriend): bool
    {
        return match ((int)($permission ?? 0)) {
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

    private function storeUserImage(UploadedFile $file, string $directory, int $userId): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = sprintf('%d_%s.%s', $userId, Str::lower(Str::random(32)), $extension);
        $path = 'images/' . trim($directory, '/') . '/' . $filename;
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || !Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('Не удалось сохранить изображение профиля.');
        }

        return $filename;
    }

    private function promoteTemporaryAvatar(?string $temporaryAvatar, int $userId): ?string
    {
        if (!$temporaryAvatar) {
            return null;
        }

        $filename = basename($temporaryAvatar);

        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $filename)) {
            throw new RuntimeException('Некорректное имя файла аватара.');
        }

        $disk = Storage::disk('public');
        $source = 'images/tmp/profile/avatar/' . $filename;

        if (!$disk->exists($source)) {
            throw new RuntimeException('Файл аватара не найден.');
        }

        $targetFilename = sprintf('%d_%s', $userId, preg_replace('/^\d+_/', '', $filename));
        $target = 'images/user/avatar/' . $targetFilename;

        if (!$disk->copy($source, $target)) {
            throw new RuntimeException('Не удалось сохранить аватар.');
        }

        $disk->delete($source);

        return $targetFilename;
    }

    private function imageResource(UploadedFile $file): \GdImage
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => false,
        };

        if (!$image instanceof \GdImage) {
            throw new RuntimeException('Неверный формат изображения.');
        }

        return $mime === 'image/jpeg' || $mime === 'image/jpg'
            ? $this->orientJpeg($image, $path)
            : $image;
    }

    private function orientJpeg(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int)($exif['Orientation'] ?? 0) : 0;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if (!$rotated instanceof \GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function deleteUserImage(string $directory, ?string $filename): void
    {
        if (!$filename) {
            return;
        }

        Storage::disk('public')->delete('images/' . trim($directory, '/') . '/' . $filename);
    }

    private function detectOs(string $userAgent): string
    {
        return match (true) {
            stripos($userAgent, 'Windows') !== false => 'Windows',
            stripos($userAgent, 'Mac OS') !== false || stripos($userAgent, 'Macintosh') !== false => 'macOS',
            stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false => 'iOS',
            stripos($userAgent, 'Android') !== false => 'Android',
            stripos($userAgent, 'Linux') !== false => 'Linux',
            default => 'Не определено',
        };
    }

    private function detectBrowser(string $userAgent): string
    {
        return match (true) {
            stripos($userAgent, 'Edg') !== false => 'Edge',
            stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false => 'Opera',
            stripos($userAgent, 'Firefox') !== false => 'Firefox',
            stripos($userAgent, 'Chrome') !== false => 'Chrome',
            stripos($userAgent, 'Safari') !== false => 'Safari',
            default => 'Не определено',
        };
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
