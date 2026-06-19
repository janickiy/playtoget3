<?php

namespace App\Repositories;

use App\DTO\Event\EventData;
use App\Enums\CommunityStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\UserStatus;
use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friend;
use App\Models\GeoCity;
use App\Models\SportType;
use App\Models\User;
use App\Repositories\Concerns\SyncsGeoTargets;
use App\Service\EventCoverService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventRepository extends BaseRepository
{
    use SyncsGeoTargets;

    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(
        Event $model,
        private readonly EventCoverService $covers
    )
    {
        parent::__construct($model);
    }

    /**
     * Returns upcoming event for calendar.
     *
     * @param int $limit
     * @return Collection
     */
    public function upcoming(int $limit = 30): Collection
    {
        return $this->model->newQuery()
            ->whereIn('status', EventStatus::visibleValues())
            ->orderByRaw('date_from IS NULL')
            ->orderBy('date_from')
            ->limit($limit)
            ->get();
    }

    /**
     * Returns the days of the month for which the event is scheduled.
     *
     * @param CarbonImmutable $monthStart
     * @param CarbonImmutable $monthEnd
     * @return Collection
     */
    public function calendarDays(CarbonImmutable $monthStart, CarbonImmutable $monthEnd): Collection
    {
        $events = $this->model->newQuery()
            ->whereIn('events.status', EventStatus::visibleValues())
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
     * Returns popular events with filters and pagination.
     *
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

    /**
     * Counts popular events taking into account filters.
     */
    public function popularEventsCount(array $filters = []): int
    {
        return (int) $this->eventListQuery($filters)->count();
    }

    /**
     * Returns event user.
     *
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
     * Counts event user.
     *
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
     * Returns events to which the user is invited.
     *
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
     * Counts the events to which the user is invited.
     *
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

    /**
     * Finds active record by ID
     *
     * @param int $eventId
     * @return Event|null
     */
    public function findActive(int $eventId): ?Event
    {
        /** @var Event|null $event */
        $event = $this->model->newQuery()
            ->whereKey($eventId)
            ->whereIn('status', EventStatus::visibleValues())
            ->first();

        return $event;
    }

    /**
     * Converts the model to a data array for docs
     *
     * @param Event $event
     * @return array
     */
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

    /**
     * Converts event to a data array for a list.
     *
     * @param Event $event
     * @param User|null $viewer
     * @param int|null $viewerRole
     * @return array
     */
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
            'date_from' => $event->date_from ? 'Start: ' . $event->date_from->format('d.m.Y at H:i') : '',
            'date_to' => $event->date_to ? 'End: ' . $event->date_to->format('d.m.Y at H:i') : '',
            'description' => (string) $event->description,
            'role' => $role !== null ? mb_strtolower(MembershipRole::labelFor((int) $role)) : '',
            'participants' => 'Participants: ' . $participantsCount . ' ' . $this->personWord($participantsCount),
            'active' => ! $event->date_to || $event->date_to->isFuture(),
            'status' => ! $event->date_to || $event->date_to->isFuture()
                ? 'Event is in progress'
                : 'Event has ended',
            'can_edit' => in_array($role, [1, 2], true),
        ];
    }

    /**
     * Returns the numeric role user in the entity.
     *
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

    /**
     * Returns the Russian name of the role by its code.
     *
     * @param int|null $role
     * @return string
     */
    public function roleLabel(?int $role): string
    {
        return MembershipRole::labelFor($role);
    }

    /**
     * Returns string participation type user.
     *
     * @param Event $event
     * @param User|null $viewer
     * @return string
     */
    public function membershipType(Event $event, ?User $viewer): string
    {
        return MembershipRole::membershipTypeFor($this->role((int) $event->id, $viewer?->id));
    }

    /**
     * Checks whether the user can manage the entity.
     *
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
     * Checks whether the user can invite members.
     *
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
     * Returns a set of user rights for the current entity.
     *
     * @param Event $event
     * @param User|null $viewer
     * @return bool[]
     */
    public function permissions(Event $event, ?User $viewer): array
    {
        $role = $this->role((int) $event->id, $viewer?->id);
        $blocked = $role === MembershipRole::Blocked->value;

        return [
            'wall' => ! $blocked,
            'photo' => ! $blocked,
            'video' => ! $blocked,
            'blocked_by_event' => $blocked,
        ];
    }

    /**
     * Returns members entity.
     *
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
     * Returns participation requests for the selected entity.
     *
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
     * Returns community linked to event.
     *
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
                    ->whereIn('status', CommunityStatus::visibleValues())
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
     * Counts members of the specified type.
     *
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
     * Changes the user's membership status in an entity.
     *
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
     * Invites the user's friends to the selected entity.
     *
     * @param Event $event
     * @param User $viewer
     * @return int
     */
    public function inviteFriends(Event $event, User $viewer): int
    {
        return $this->inviteFriendUsers($event, $viewer)->count();
    }

    /**
     * Returns user friends who can be invited to the event.
     *
     * @param Event $event
     * @param User $viewer
     * @return Collection
     */
    public function invitableFriends(Event $event, User $viewer): Collection
    {
        if (! $this->canInvite($event, $viewer)) {
            return collect();
        }

        $friendIds = $this->availableFriendIds($event, $viewer);

        if ($friendIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $friendIds)
            ->where('status', UserStatus::Confirmed->value)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->orderBy('email')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->displayName(),
                'city' => (string) $user->city,
                'avatar' => FrontAssets::userAvatar($user),
            ]);
    }

    /**
     * Invites the selected user's friends to the event and returns the invitees.
     *
     * @param Event $event
     * @param User $viewer
     * @param array $friendIds
     * @return Collection
     */
    public function inviteFriendUsers(Event $event, User $viewer, array $friendIds = []): Collection
    {
        if (! $this->canInvite($event, $viewer)) {
            return collect();
        }

        $availableIds = $this->availableFriendIds($event, $viewer);
        $selectedIds = collect($friendIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $inviteIds = $selectedIds->isNotEmpty()
            ? $availableIds->intersect($selectedIds)->values()
            : $availableIds;

        if ($inviteIds->isEmpty()) {
            return collect();
        }

        $invitees = User::query()
            ->whereIn('id', $inviteIds)
            ->where('status', UserStatus::Confirmed->value)
            ->get()
            ->keyBy('id');

        if ($invitees->isEmpty()) {
            return collect();
        }

        AcceptedEventMember::query()->insert($invitees->keys()->map(fn (int $id): array => [
            'event_id' => $event->id,
            'eventable_type' => 'user',
            'member_id' => $id,
            'role' => MembershipRole::Invited->value,
        ])->all());

        return $invitees->values();
    }

    /**
     * Returns id of friends who do not yet have a role in the selected event.
     *
     * @param Event $event
     * @param User $viewer
     * @return Collection
     */
    private function availableFriendIds(Event $event, User $viewer): Collection
    {
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
            return collect();
        }

        $existingIds = AcceptedEventMember::query()
            ->where('event_id', $event->id)
            ->where('eventable_type', 'user')
            ->whereIn('member_id', $friendIds)
            ->pluck('member_id')
            ->map(fn ($id): int => (int) $id);

        return $friendIds->diff($existingIds)->values();
    }

    /**
     * Creates event and associated owner data.
     *
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
                'status' => EventStatus::Confirmed->value,
            ]);

            AcceptedEventMember::query()->create([
                'event_id' => $event->id,
                'eventable_type' => 'user',
                'member_id' => $owner->id,
                'role' => 1,
            ]);

            $this->syncGeoTarget('event', (int) $event->id, $data->cityId);

            return $event;
        });
    }

    /**
     * Updates event and related data.
     *
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
            $this->syncGeoTarget('event', (int) $event->id, $data->cityId);

            if ($cover && $oldCover) {
                $this->covers->deleteCover($oldCover);
            }

            return true;
        });
    }

    /**
     * Returns name city by its identifier.
     *
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
     * Prepares a basic event list query.
     *
     * @param array $filters
     * @return Builder
     */
    private function eventListQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()
            ->whereIn('events.status', EventStatus::visibleValues());

        $this->applyEventFilters($query, $filters);

        return $query;
    }

    /**
     * Applies search filters to the events query.
     *
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

    /**
     * Returns name sport type by identifier.
     */
    private function sportName(int $sportId): string
    {
        if ($sportId < 1) {
            return '';
        }

        return (string) (SportType::query()->find($sportId)?->name ?? '');
    }

    /**
     * Converts members event-user to a data array.
     *
     * @param AcceptedEventMember $member
     * @return array|null
     */
    private function serializeUserMember(AcceptedEventMember $member): ?array
    {
        $user = $member->member;

        if (! $user instanceof User || ! $user->isActive()) {
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
            'is_online' => $user->isOnline(),
        ];
    }

    /**
     * Selects the correct form of the word for the number of members.
     *
     * @param int $count
     * @return string
     */
    private function personWord(int $count): string
    {
        $lastTwo = $count % 100;
        $last = $count % 10;

        if ($lastTwo >= 11 && $lastTwo <= 14) {
            return 'people';
        }

        return match ($last) {
            1 => 'person',
            2, 3, 4 => 'people',
            default => 'people',
        };
    }

}
