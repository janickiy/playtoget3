<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friend;
use App\Models\GeoCity;
use App\Models\GeoTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventRepository extends BaseRepository
{
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    public function upcoming(int $limit = 30): Collection
    {
        return $this->model->newQuery()
            ->where('banned', false)
            ->orderByRaw('date_from IS NULL')
            ->orderBy('date_from')
            ->limit($limit)
            ->get();
    }

    public function findActive(int $eventId): ?Event
    {
        /** @var Event|null $event */
        $event = $this->model->newQuery()
            ->whereKey($eventId)
            ->where('banned', false)
            ->first();

        return $event;
    }

    public function serialize(Event $event): array
    {
        return [
            'id' => (int) $event->id,
            'name' => (string) $event->name,
            'description' => (string) $event->description,
            'cover' => FrontAssets::eventCover($event),
            'place' => (string) $event->place,
            'address' => (string) $event->address,
            'sport_type' => (string) $event->sport_type,
            'date_from' => $event->date_from?->format('d.m.Y H:i') ?? '',
            'date_to' => $event->date_to?->format('d.m.Y H:i') ?? '',
            'date_from_value' => $event->date_from?->format('Y-m-d\TH:i') ?? '',
            'date_to_value' => $event->date_to?->format('Y-m-d\TH:i') ?? '',
            'active' => ! $event->date_to || $event->date_to->isFuture(),
            'members_count' => $this->membersCount($event->id),
        ];
    }

    public function role(int $eventId, ?int $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        $role = AcceptedEventMember::query()
            ->where('event_id', $eventId)
            ->where('eventable_type', 'user')
            ->where('member_id', $userId)
            ->value('role');

        return $role === null ? null : (int) $role;
    }

    public function roleLabel(?int $role): string
    {
        return $role === null ? '' : $this->roleName($role);
    }

    public function membershipType(Event $event, ?User $viewer): string
    {
        return match ($this->role((int) $event->id, $viewer?->id)) {
            1 => 'owner',
            2 => 'admin',
            3 => 'member',
            0 => 'applied',
            4 => 'blocked',
            5 => 'invited',
            default => 'none',
        };
    }

    public function canManage(?Event $event, ?User $viewer): bool
    {
        if (! $event || ! $viewer) {
            return false;
        }

        return in_array($this->role((int) $event->id, (int) $viewer->id), [1, 2], true);
    }

    public function canInvite(?Event $event, ?User $viewer): bool
    {
        if (! $event || ! $viewer) {
            return false;
        }

        return in_array($this->role((int) $event->id, (int) $viewer->id), [1, 2, 3], true);
    }

    public function permissions(Event $event, ?User $viewer): array
    {
        $role = $this->role((int) $event->id, $viewer?->id);

        return [
            'wall' => $role !== 4,
            'photo' => $role !== 4,
            'video' => $role !== 4,
        ];
    }

    public function members(int $eventId): Collection
    {
        return AcceptedEventMember::query()
            ->with('member.activity')
            ->where('event_id', $eventId)
            ->where('eventable_type', 'user')
            ->whereIn('role', [1, 2, 3])
            ->orderBy('role')
            ->get()
            ->map(fn (AcceptedEventMember $member): ?array => $this->serializeUserMember($member))
            ->filter()
            ->values();
    }

    public function applications(int $eventId): Collection
    {
        return AcceptedEventMember::query()
            ->with('member.activity')
            ->where('event_id', $eventId)
            ->where('eventable_type', 'user')
            ->where('role', 0)
            ->get()
            ->map(fn (AcceptedEventMember $member): ?array => $this->serializeUserMember($member))
            ->filter()
            ->values();
    }

    public function communities(int $eventId, string $type): Collection
    {
        return AcceptedEventMember::query()
            ->where('event_id', $eventId)
            ->where('eventable_type', $type)
            ->whereIn('role', [1, 2, 3])
            ->pluck('member_id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->pipe(function (Collection $ids) use ($type): Collection {
                if ($ids->isEmpty()) {
                    return collect();
                }

                return Community::query()
                    ->whereIn('id', $ids)
                    ->where('type', $type)
                    ->where('banned', false)
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Community $community): array => [
                        'id' => (int) $community->id,
                        'name' => (string) $community->name,
                        'avatar' => FrontAssets::communityAvatar($community),
                        'place' => (string) $community->place,
                        'sport_type' => (string) $community->sport_type,
                        'url' => route($type === 'group' ? 'front.groups.show' : 'front.teams.show', ['community' => $community->id]),
                    ]);
            });
    }

    public function membersCount(int $eventId, string $type = 'user'): int
    {
        return AcceptedEventMember::query()
            ->where('event_id', $eventId)
            ->where('eventable_type', $type)
            ->whereIn('role', [1, 2, 3])
            ->count();
    }

    public function changeMembership(Event $event, User $viewer, int $status): bool
    {
        if (! in_array($status, [0, 1], true)) {
            return false;
        }

        return DB::transaction(function () use ($event, $viewer, $status): bool {
            /** @var AcceptedEventMember|null $member */
            $member = AcceptedEventMember::query()
                ->where('event_id', $event->id)
                ->where('eventable_type', 'user')
                ->where('member_id', $viewer->id)
                ->lockForUpdate()
                ->first();

            if ($status === 0) {
                return $member ? (bool) $member->delete() : false;
            }

            if ($member && (int) $member->role === 4) {
                return false;
            }

            if ($member && in_array((int) $member->role, [1, 2, 3], true)) {
                return true;
            }

            if ($member) {
                $member->fill(['role' => 3])->save();

                return true;
            }

            AcceptedEventMember::query()->create([
                'event_id' => $event->id,
                'eventable_type' => 'user',
                'member_id' => $viewer->id,
                'role' => 3,
            ]);

            return true;
        });
    }

    public function inviteFriends(Event $event, User $viewer): int
    {
        if (! $this->canInvite($event, $viewer)) {
            return 0;
        }

        $friendIds = Friend::query()
            ->where('status', 1)
            ->where(function (Builder $query) use ($viewer): void {
                $query
                    ->where('user_id', $viewer->id)
                    ->orWhere('friend_id', $viewer->id);
            })
            ->get(['user_id', 'friend_id'])
            ->map(fn (Friend $friend): int => (int) ((int) $friend->user_id === (int) $viewer->id ? $friend->friend_id : $friend->user_id))
            ->filter(fn (int $id): bool => $id > 0 && $id !== (int) $viewer->id)
            ->unique()
            ->values();

        if ($friendIds->isEmpty()) {
            return 0;
        }

        $existingIds = AcceptedEventMember::query()
            ->where('event_id', $event->id)
            ->where('eventable_type', 'user')
            ->whereIn('member_id', $friendIds)
            ->pluck('member_id')
            ->map(fn ($id): int => (int) $id);

        $inviteIds = User::query()
            ->whereIn('id', $friendIds->diff($existingIds)->values())
            ->where('confirmed', true)
            ->where('banned', false)
            ->where('deleted', false)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        if ($inviteIds->isEmpty()) {
            return 0;
        }

        AcceptedEventMember::query()->insert($inviteIds->map(fn (int $id): array => [
            'event_id' => $event->id,
            'eventable_type' => 'user',
            'member_id' => $id,
            'role' => 5,
        ])->all());

        return $inviteIds->count();
    }

    public function createEvent(User $owner, array $data): Event
    {
        return DB::transaction(function () use ($owner, $data): Event {
            /** @var Event $event */
            $event = $this->model->newQuery()->create([
                'name' => $data['name'],
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'description' => $data['description'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
                'cover_page' => $this->storeCover($data['cover_file'] ?? null) ?? '',
                'place' => $data['place'] ?? '',
                'address' => $data['address'] ?? '',
                'moderate' => true,
                'banned' => false,
            ]);

            AcceptedEventMember::query()->create([
                'event_id' => $event->id,
                'eventable_type' => 'user',
                'member_id' => $owner->id,
                'role' => 1,
            ]);

            $this->syncGeoTarget($event, (int) ($data['city_id'] ?? 0));

            return $event;
        });
    }

    public function updateEvent(Event $event, array $data): bool
    {
        return DB::transaction(function () use ($event, $data): bool {
            $oldCover = (string) $event->cover_page;
            $cover = $this->storeCover($data['cover_file'] ?? null);
            $fields = [
                'name' => $data['name'],
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'description' => $data['description'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
                'place' => $data['place'] ?? '',
                'address' => $data['address'] ?? '',
            ];

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $event->fill($fields)->save();
            $this->syncGeoTarget($event, (int) ($data['city_id'] ?? 0));

            if ($cover && $oldCover) {
                $this->deleteCover($oldCover);
            }

            return true;
        });
    }

    public function cityName(?int $cityId): string
    {
        if (! $cityId) {
            return '';
        }

        return (string) (GeoCity::query()->find($cityId)?->name_ru ?? '');
    }

    private function serializeUserMember(AcceptedEventMember $member): ?array
    {
        $user = $member->member;

        if (! $user instanceof User || $user->banned || $user->deleted) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
            'firstname' => (string) $user->firstname,
            'lastname' => (string) $user->lastname,
            'avatar' => FrontAssets::userAvatar($user),
            'city' => (string) $user->city,
            'role' => (int) $member->role,
            'role_name' => $this->roleName((int) $member->role),
            'is_online' => false,
        ];
    }

    private function roleName(int $role): string
    {
        return match ($role) {
            1 => 'Владелец',
            2 => 'Администратор',
            3 => 'Участник',
            0 => 'Заявка',
            4 => 'Заблокирован',
            5 => 'Приглашен',
            default => '',
        };
    }

    private function syncGeoTarget(Event $event, int $cityId): void
    {
        if ($cityId < 1) {
            return;
        }

        GeoTarget::query()->updateOrCreate([
            'target_type' => 'event',
            'target_id' => $event->id,
        ], [
            'city_id' => $cityId,
        ]);
    }

    private function storeCover(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return Storage::disk('public')->putFileAs('images/events/cover_page', $file, $filename)
            ? $filename
            : null;
    }

    private function deleteCover(string $filename): void
    {
        Storage::disk('public')->delete('images/events/cover_page/' . $filename);

        $legacyPath = public_path('uploads/images/events/cover_page/' . $filename);
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
