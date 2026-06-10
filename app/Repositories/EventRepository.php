<?php

namespace App\Repositories;

use App\DTO\Event\EventData;
use App\Enums\MembershipRole;
use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friend;
use App\Models\GeoCity;
use App\Models\GeoTarget;
use App\Models\SportType;
use App\Models\User;
use App\Service\EventCoverService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventRepository extends BaseRepository
{
    public function __construct(
        Event $model,
        private readonly EventCoverService $covers
    )
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

    /**
     * @param CarbonImmutable $monthStart
     * @param CarbonImmutable $monthEnd
     * @return Collection
     */
    public function calendarDays(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        $events = $this->model->newQuery()
            ->where('events.banned', false)
            ->whereNotNull('events.date_from')
            ->whereDate('events.date_from', '<=', $monthEnd->toDateString())
            ->where(function (Builder $query) use ($monthStart): void {
                $query
                    ->whereDate('events.date_from', '>=', $monthStart->toDateString())
                    ->orWhereDate('events.date_to', '>=', $monthStart->toDateString());
            })
            ->orderBy('events.date_from')
            ->get();

        $days = [];

        foreach ($events as $event) {
            if (! $event->date_from) {
                continue;
            }

            $eventStart = CarbonImmutable::instance($event->date_from)->startOfDay();
            $eventEnd = $event->date_to
                ? CarbonImmutable::instance($event->date_to)->startOfDay()
                : $eventStart;
            $periodStart = $eventStart->lessThan($monthStart) ? $monthStart : $eventStart;
            $periodEnd = $eventEnd->greaterThan($monthEnd) ? $monthEnd : $eventEnd;

            foreach (CarbonPeriod::create($periodStart, '1 day', $periodEnd) as $date) {
                $key = $date->format('Y-m-d');
                $days[$key] ??= [
                    'date' => $key,
                    'count' => 0,
                    'events' => [],
                ];
                $days[$key]['count']++;

                if (count($days[$key]['events']) < 3) {
                    $days[$key]['events'][] = [
                        'id' => (int) $event->id,
                        'name' => (string) $event->name,
                        'time' => $event->date_from?->format('H:i') ?? '',
                    ];
                }
            }
        }

        ksort($days);

        return collect($days);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @param User|null $viewer
     * @return Collection
     */
    public function popularEvents(int $limit = 5, int $offset = 0, array $filters = [], ?User $viewer = null): Collection
    {
        $query = $this->eventListQuery($filters);

        return $query
            ->withCount(['acceptedMembers as members_count' => fn (Builder $query) => $query
                ->where('eventable_type', 'user')
                ->whereIn('role', [1, 2, 3])])
            ->orderByDesc('members_count')
            ->orderByRaw('events.date_from IS NULL')
            ->orderBy('events.date_from')
            ->orderBy('events.name')
            ->offset(max($offset, 0))
            ->limit(max($limit, 1))
            ->get()
            ->map(fn (Event $event): array => $this->serializeListEvent($event, $viewer));
    }

    public function popularEventsCount(array $filters = []): int
    {
        return (int) $this->eventListQuery($filters)->count();
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function myEvents(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->eventListQuery($filters)
            ->join('accepted_event_members as event_members', 'event_members.event_id', '=', 'events.id')
            ->where('event_members.eventable_type', 'user')
            ->where('event_members.member_id', $userId)
            ->whereIn('event_members.role', [1, 2, 3])
            ->select('events.*', 'event_members.role as viewer_role');

        return $query
            ->withCount(['acceptedMembers as members_count' => fn (Builder $query) => $query
                ->where('eventable_type', 'user')
                ->whereIn('role', [1, 2, 3])])
            ->orderBy('event_members.role')
            ->orderByRaw('events.date_from IS NULL')
            ->orderByDesc('events.date_from')
            ->offset(max($offset, 0))
            ->limit(max($limit, 1))
            ->get()
            ->map(fn (Event $event): array => $this->serializeListEvent($event, null, (int) $event->viewer_role));
    }

    /**
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function myEventsCount(int $userId, array $filters = []): int
    {
        $query = $this->eventListQuery($filters)
            ->join('accepted_event_members as event_members', 'event_members.event_id', '=', 'events.id')
            ->where('event_members.eventable_type', 'user')
            ->where('event_members.member_id', $userId)
            ->whereIn('event_members.role', [1, 2, 3]);

        return (int) $query->count(DB::raw('distinct events.id'));
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function invitedEvents(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->eventListQuery($filters)
            ->join('accepted_event_members as event_members', 'event_members.event_id', '=', 'events.id')
            ->where('event_members.eventable_type', 'user')
            ->where('event_members.member_id', $userId)
            ->where('event_members.role', 5)
            ->select('events.*', 'event_members.role as viewer_role');

        return $query
            ->withCount(['acceptedMembers as members_count' => fn (Builder $query) => $query
                ->where('eventable_type', 'user')
                ->whereIn('role', [1, 2, 3])])
            ->orderByRaw('events.date_from IS NULL')
            ->orderByDesc('events.date_from')
            ->offset(max($offset, 0))
            ->limit(max($limit, 1))
            ->get()
            ->map(fn (Event $event): array => $this->serializeListEvent($event, null, 5));
    }

    /**
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function invitedEventsCount(int $userId, array $filters = []): int
    {
        $query = $this->eventListQuery($filters)
            ->join('accepted_event_members as event_members', 'event_members.event_id', '=', 'events.id')
            ->where('event_members.eventable_type', 'user')
            ->where('event_members.member_id', $userId)
            ->where('event_members.role', 5);

        return (int) $query->count(DB::raw('distinct events.id'));
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

    public function serializeListEvent(Event $event, ?User $viewer = null, ?int $viewerRole = null): array
    {
        $role = $viewerRole ?? $this->role((int) $event->id, $viewer?->id);
        $participantsCount = (int) ($event->members_count ?? $this->membersCount($event->id));

        return [
            'id' => (int) $event->id,
            'name' => (string) $event->name,
            'avatar' => FrontAssets::eventAvatar($event),
            'sport_type' => (string) $event->sport_type,
            'city' => (string) $event->place,
            'date_from' => $event->date_from ? 'Начало: ' . $event->date_from->format('d.m.Y в H:i') : '',
            'date_to' => $event->date_to ? 'Окончание: ' . $event->date_to->format('d.m.Y в H:i') : '',
            'description' => (string) $event->description,
            'role' => $role !== null ? mb_strtolower(MembershipRole::labelFor((int) $role)) : '',
            'participants' => 'Участвуют ' . $participantsCount . ' ' . $this->personWord($participantsCount),
            'active' => ! $event->date_to || $event->date_to->isFuture(),
            'status' => ! $event->date_to || $event->date_to->isFuture()
                ? 'Мероприятие продолжается'
                : 'Мероприятие завершено',
            'can_edit' => in_array($role, [1, 2], true),
        ];
    }

    /**
     * @param int $eventId
     * @param int|null $userId
     * @return int|null
     */
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
        return MembershipRole::labelFor($role);
    }

    /**
     * @param Event $event
     * @param User|null $viewer
     * @return string
     */
    public function membershipType(Event $event, ?User $viewer): string
    {
        return MembershipRole::membershipTypeFor($this->role((int) $event->id, $viewer?->id));
    }

    /**
     * @param Event|null $event
     * @param User|null $viewer
     * @return bool
     */
    public function canManage(?Event $event, ?User $viewer): bool
    {
        if (! $event || ! $viewer) {
            return false;
        }

        return in_array($this->role((int) $event->id, (int) $viewer->id), [1, 2], true);
    }

    /**
     * @param Event|null $event
     * @param User|null $viewer
     * @return bool
     */
    public function canInvite(?Event $event, ?User $viewer): bool
    {
        if (! $event || ! $viewer) {
            return false;
        }

        return in_array($this->role((int) $event->id, (int) $viewer->id), [1, 2, 3], true);
    }

    /**
     * @param Event $event
     * @param User|null $viewer
     * @return bool[]
     */
    public function permissions(Event $event, ?User $viewer): array
    {
        $role = $this->role((int) $event->id, $viewer?->id);

        return [
            'wall' => $role !== 4,
            'photo' => $role !== 4,
            'video' => $role !== 4,
        ];
    }

    /**
     * @param int $eventId
     * @return Collection
     */
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

    /**
     * @param int $eventId
     * @return Collection
     */
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

    /**
     * @param int $eventId
     * @param string $type
     * @return Collection
     */
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

    /**
     * @param int $eventId
     * @param string $type
     * @return int
     */
    public function membersCount(int $eventId, string $type = 'user'): int
    {
        return AcceptedEventMember::query()
            ->where('event_id', $eventId)
            ->where('eventable_type', $type)
            ->whereIn('role', [1, 2, 3])
            ->count();
    }

    /**
     * @param Event $event
     * @param User $viewer
     * @param int $status
     * @return bool
     * @throws \Throwable
     */
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

    /**
     * @param Event $event
     * @param User $viewer
     * @return int
     */
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

    /**
     * @param User $owner
     * @param EventData $data
     * @return Event
     * @throws \Throwable
     */
    public function createEvent(User $owner, EventData $data): Event
    {
        return DB::transaction(function () use ($owner, $data): Event {
            /** @var Event $event */
            $event = $this->model->newQuery()->create([
                'name' => $data->name,
                'date_from' => $data->dateFrom,
                'date_to' => $data->dateTo,
                'description' => $data->description,
                'sport_type' => $data->sportType,
                'cover_page' => $this->covers->storeCover($data->coverFile) ?? '',
                'place' => $data->place ?: $this->cityName($data->cityId),
                'address' => $data->address,
                'moderate' => true,
                'banned' => false,
            ]);

            AcceptedEventMember::query()->create([
                'event_id' => $event->id,
                'eventable_type' => 'user',
                'member_id' => $owner->id,
                'role' => 1,
            ]);

            $this->syncGeoTarget($event, $data->cityId);

            return $event;
        });
    }

    /**
     * @param Event $event
     * @param EventData $data
     * @return bool
     * @throws \Throwable
     */
    public function updateEvent(Event $event, EventData $data): bool
    {
        return DB::transaction(function () use ($event, $data): bool {
            $oldCover = (string) $event->cover_page;
            $cover = $this->covers->storeCover($data->coverFile);
            $fields = [
                'name' => $data->name,
                'date_from' => $data->dateFrom,
                'date_to' => $data->dateTo,
                'description' => $data->description,
                'sport_type' => $data->sportType,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'address' => $data->address,
            ];

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $event->fill($fields)->save();
            $this->syncGeoTarget($event, $data->cityId);

            if ($cover && $oldCover) {
                $this->covers->deleteCover($oldCover);
            }

            return true;
        });
    }

    /**
     * @param int|null $cityId
     * @return string
     */
    public function cityName(?int $cityId): string
    {
        if (! $cityId) {
            return '';
        }

        return (string) (GeoCity::query()->find($cityId)?->name_ru ?? '');
    }

    /**
     * @param array $filters
     * @return Builder
     */
    private function eventListQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()
            ->where('events.banned', false);

        $this->applyEventFilters($query, $filters);

        return $query;
    }

    /**
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyEventFilters(Builder $query, array $filters): void
    {
        $place = trim((string) ($filters['place'] ?? ''));
        $sport = trim((string) ($filters['sport'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $date = trim((string) ($filters['date'] ?? ''));

        if ($place === '' && (int) ($filters['id_place'] ?? 0) > 0) {
            $place = $this->cityName((int) $filters['id_place']);
        }

        if ($sport === '' && (int) ($filters['id_sport'] ?? 0) > 0) {
            $sport = $this->sportName((int) $filters['id_sport']);
        }

        if ($place !== '') {
            $query->where('events.place', 'like', '%' . $place . '%');
        }

        if ($sport !== '') {
            $query->where('events.sport_type', 'like', '%' . $sport . '%');
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('events.name', 'like', '%' . $search . '%')
                    ->orWhere('events.description', 'like', '%' . $search . '%')
                    ->orWhere('events.place', 'like', '%' . $search . '%')
                    ->orWhere('events.sport_type', 'like', '%' . $search . '%');
            });
        }

        if ($date !== '') {
            $query
                ->whereNotNull('events.date_from')
                ->whereDate('events.date_from', '<=', $date)
                ->where(function (Builder $query) use ($date): void {
                    $query
                        ->whereDate('events.date_from', $date)
                        ->orWhereDate('events.date_to', '>=', $date);
                });
        }
    }

    private function sportName(int $sportId): string
    {
        if ($sportId < 1) {
            return '';
        }

        return (string) (SportType::query()->find($sportId)?->name ?? '');
    }

    /**
     * @param AcceptedEventMember $member
     * @return array|null
     */
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
            'role_name' => MembershipRole::labelFor((int) $member->role),
            'is_online' => false,
        ];
    }

    private function personWord(int $count): string
    {
        $lastTwo = $count % 100;
        $last = $count % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return 'человек';
        }

        return match ($last) {
            1 => 'человек',
            2, 3, 4 => 'человека',
            default => 'человек',
        };
    }

    /**
     * @param Event $event
     * @param int $cityId
     * @return void
     */
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

}
