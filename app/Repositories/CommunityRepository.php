<?php

namespace App\Repositories;

use App\DTO\Community\CommunityData;
use App\Enums\CommunityPrivacyType;
use App\Enums\CommunityStatus;
use App\Enums\EventStatus;
use App\Enums\MembershipRole;
use App\Enums\UserStatus;
use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\CommunitySetting;
use App\Models\Event;
use App\Models\Friend;
use App\Models\GeoCity;
use App\Models\SportType;
use App\Models\User;
use App\Repositories\Concerns\SyncsGeoTargets;
use App\Service\CommunityImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommunityRepository extends BaseRepository
{
    use SyncsGeoTargets;

    /**
     * Connects модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(
        Community $model,
        private readonly CommunityImageService $images,
        private readonly FriendRepository $friends,
    )
    {
        parent::__construct($model);
    }

    /**
     * Returns list teams.
     */
    public function teams(): Collection
    {
        return $this->model->newQuery()
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->whereIn('status', CommunityStatus::visibleValues())
            ->orderBy('name')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    /**
     * Finds team по идентификатору.
     *
     * @param int $id
     * @return Community|null
     */
    public function findTeam(int $id): ?Community
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()
            ->with(['settings', 'roles.user'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->whereIn('status', CommunityStatus::visibleValues())
            ->whereKey($id)
            ->first();

        return $community;
    }

    /**
     * Finds group по идентификатору.
     *
     * @param int $id
     * @return Community|null
     */
    public function findGroup(int $id): ?Community
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()
            ->with(['settings', 'roles.user'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'group')
            ->whereIn('status', CommunityStatus::visibleValues())
            ->whereKey($id)
            ->first();

        return $community;
    }

    /**
     * Returns team по умолчанию для current контекста.
     *
     * @param User|null $viewer
     * @return Community|null
     */
    public function defaultTeam(?User $viewer = null): ?Community
    {
        if ($viewer) {
            $team = $this->model->newQuery()
                ->where('communities.type', 'team')
                ->whereIn('communities.status', CommunityStatus::visibleValues())
                ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
                ->where('community_roles.user_id', $viewer->id)
                ->whereIn('community_roles.role', [1, 2, 3])
                ->orderBy('community_roles.role')
                ->select('communities.*')
                ->first();

            if ($team) {
                return $this->findTeam((int) $team->id);
            }
        }

        return $this->findTeam(18) ?: $this->model->newQuery()
            ->where('type', 'team')
            ->whereIn('status', CommunityStatus::visibleValues())
            ->orderBy('id')
            ->first();
    }

    /**
     * Returns group по умолчанию для current контекста.
     *
     * @param User|null $viewer
     * @return Community|null
     */
    public function defaultGroup(?User $viewer = null): ?Community
    {
        if ($viewer) {
            $group = $this->model->newQuery()
                ->where('communities.type', 'group')
                ->whereIn('communities.status', CommunityStatus::visibleValues())
                ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
                ->where('community_roles.user_id', $viewer->id)
                ->whereIn('community_roles.role', [1, 2, 3])
                ->orderBy('community_roles.role')
                ->select('communities.*')
                ->first();

            if ($group) {
                return $this->findGroup((int) $group->id);
            }
        }

        return $this->model->newQuery()
            ->where('type', 'group')
            ->whereIn('status', CommunityStatus::visibleValues())
            ->orderBy('id')
            ->first();
    }

    /**
     * Finds team, которой принадлежит album.
     */
    public function teamForAlbumOwner(int $ownerId): ?Community
    {
        return $this->findTeam($ownerId);
    }

    /**
     * Returns team user.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function myTeams(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'team')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return $query
            ->orderBy('community_roles.role')
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    /**
     * Считает team user.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function myTeamsCount(int $userId, array $filters = []): int
    {
        $query = $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'team')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return (int) $query->count(DB::raw('distinct communities.id'));
    }

    /**
     * Returns популярные team с фильтрами и пагинацией.
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function popularTeams(int $limit = 5, int $offset = 0, array $filters = [], ?User $viewer = null): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->whereIn('status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);
        $this->applyPrivateVisibility($query, $viewer);

        return $query
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    /**
     * Считает популярные team с учетом фильтров.
     */
    public function popularTeamsCount(array $filters = [], ?User $viewer = null): int
    {
        $query = $this->model->newQuery()
            ->where('type', 'team')
            ->whereIn('status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);
        $this->applyPrivateVisibility($query, $viewer);

        return (int) $query->count();
    }

    /**
     * Returns team, where user приглашен.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function invitedTeams(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'team')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return $query
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    /**
     * Считает team, where user приглашен.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function invitedTeamsCount(int $userId, array $filters = []): int
    {
        $query = $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'team')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return (int) $query->count(DB::raw('distinct communities.id'));
    }

    /**
     * Returns group user.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function myGroups(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'group')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return $query
            ->orderBy('community_roles.role')
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    /**
     * Считает group user.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function myGroupsCount(int $userId, array $filters = []): int
    {
        $query = $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'group')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return (int) $query->count(DB::raw('distinct communities.id'));
    }

    /**
     * Returns популярные group с фильтрами и пагинацией.
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function popularGroups(int $limit = 5, int $offset = 0, array $filters = [], ?User $viewer = null): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'group')
            ->whereIn('status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);
        $this->applyPrivateVisibility($query, $viewer);

        return $query
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    /**
     * Считает популярные group с учетом фильтров.
     *
     * @param array $filters
     * @return int
     */
    public function popularGroupsCount(array $filters = [], ?User $viewer = null): int
    {
        $query = $this->model->newQuery()
            ->where('type', 'group')
            ->whereIn('status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);
        $this->applyPrivateVisibility($query, $viewer);

        return (int) $query->count();
    }

    /**
     * Returns group, where user приглашен.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function invitedGroups(int $userId, int $limit = 5, int $offset = 0, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'group')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return $query
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    /**
     * Считает group, where user приглашен.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function invitedGroupsCount(int $userId, array $filters = []): int
    {
        $query = $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'group')
            ->whereIn('communities.status', CommunityStatus::visibleValues());

        $this->applyCommunityFilters($query, $filters);

        return (int) $query->count(DB::raw('distinct communities.id'));
    }

    /**
     * Returns members entity.
     *
     * @param int $teamId
     * @return Collection
     */
    public function members(int $teamId, ?int $viewerId = null): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->whereIn('role', [1, 2, 3])
            ->orderBy('role')
            ->get()
            ->map(fn (CommunityRole $role): ?array => $this->serializeMember($role, $viewerId))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    /**
     * Returns заявки на участие для выбранной entity.
     *
     * @param int $teamId
     * @return Collection
     */
    public function applications(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 0)
            ->get()
            ->map(fn (CommunityRole $role): ?array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    /**
     * Returns administratorов community.
     *
     * @param int $teamId
     * @return Collection
     */
    public function admins(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 2)
            ->get()
            ->map(fn (CommunityRole $role): ?array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    /**
     * Returns blocked members community.
     *
     * @param int $teamId
     * @return Collection
     */
    public function blocked(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 4)
            ->get()
            ->map(fn (CommunityRole $role): ?array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    /**
     * Returns event community.
     *
     * @param int $communityId
     * @param string $eventableType
     * @return Collection
     */
    public function events(int $communityId, string $eventableType = 'team'): Collection
    {
        return Event::query()
            ->with('sportType')
            ->whereIn('status', EventStatus::visibleValues())
            ->whereHas('acceptedMembers', fn ($query) => $query
                ->where('eventable_type', $eventableType)
                ->where('member_id', $communityId))
            ->orderByDesc('date_from')
            ->get()
            ->map(fn (Event $event): array => $this->serializeEvent($event, $eventableType));
    }

    /**
     * Ищет event для привязки к teamsе.
     */
    public function searchEventsForTeam(int $teamId, string $search = '', int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        return $this->searchEventsForCommunity($teamId, 'team', $search, $limit, $offset, $filters);
    }

    /**
     * Ищет event для привязки к сообществу.
     *
     * @param int $communityId
     * @param string $eventableType
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function searchEventsForCommunity(int $communityId, string $eventableType, string $search = '', int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        $query = Event::query()
            ->with('sportType')
            ->whereIn('status', EventStatus::visibleValues())
            ->whereDoesntHave('acceptedMembers', fn ($query) => $query
                ->where('eventable_type', $eventableType)
                ->where('member_id', $communityId));

        $place = trim((string) ($filters['place'] ?? ''));
        $sport = trim((string) ($filters['sport'] ?? ''));
        $search = trim($search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $words = preg_split('/\s+/', $search) ?: [];

                foreach ($words as $word) {
                    $word = trim($word);

                    if ($word === '') {
                        continue;
                    }

                    $query->orWhere('name', 'like', '%' . $word . '%')
                        ->orWhere('description', 'like', '%' . $word . '%')
                        ->orWhere('place', 'like', '%' . $word . '%')
                        ->orWhereHas('sportType', fn (Builder $sportQuery) => $sportQuery->where('name', 'like', '%' . $word . '%'));
                }
            });
        }

        if ($place !== '') {
            $query->where('place', 'like', '%' . $place . '%');
        }

        if ($sport !== '') {
            $query->where(function (Builder $query) use ($sport): void {
                $query
                    ->where('sport_type', 'like', '%' . $sport . '%')
                    ->orWhereHas('sportType', fn (Builder $sportQuery) => $sportQuery->where('name', 'like', '%' . $sport . '%'));
            });
        }

        return $query
            ->orderBy('name')
            ->offset(max($offset, 0))
            ->limit(max($limit, 1))
            ->get()
            ->map(fn (Event $event): array => $this->serializeEvent($event, $eventableType));
    }

    /**
     * Меняет участие community в мероприятии.
     *
     * @param Community $team
     * @param int $eventId
     * @param int $status
     * @return bool
     */
    public function changeEventMembership(Community $team, int $eventId, int $status): bool
    {
        if (! in_array($status, [0, 1], true)) {
            return false;
        }

        /** @var Event|null $event */
        $event = Event::query()
            ->whereKey($eventId)
            ->whereIn('status', EventStatus::visibleValues())
            ->first();

        if (! $event) {
            return false;
        }

        $attributes = [
            'event_id' => $event->id,
            'eventable_type' => (string) $team->type,
            'member_id' => $team->id,
        ];

        if ($status === 0) {
            return AcceptedEventMember::query()->where($attributes)->delete() > 0;
        }

        AcceptedEventMember::query()->updateOrCreate($attributes, [
            'role' => 3,
        ]);

        return true;
    }

    /**
     * Returns числовую role user в entity.
     *
     * @param int $teamId
     * @param int|null $userId
     * @return int|null
     */
    public function role(int $teamId, ?int $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        return CommunityRole::query()
            ->where('community_id', $teamId)
            ->where('user_id', $userId)
            ->value('role');
    }

    /**
     * Returns русское name роли по ее коду.
     *
     * @param int|null $role
     * @return string
     */
    public function roleLabel(?int $role): string
    {
        return MembershipRole::labelFor($role);
    }

    /**
     * Checks, является ли user владельцем entity.
     *
     * @param Community|null $team
     * @param User|null $viewer
     * @return bool
     */
    public function isOwner(?Community $team, ?User $viewer): bool
    {
        if (! $team) {
            return false;
        }

        return $this->role((int) $team->id, $viewer?->id) === 1;
    }

    /**
     * Checks, может ли user управлять сущностью.
     *
     * @param Community|null $team
     * @param User|null $viewer
     * @return bool
     */
    public function canManage(?Community $team, ?User $viewer): bool
    {
        if (! $team) {
            return false;
        }

        return in_array($this->role((int) $team->id, $viewer?->id), [1, 2], true);
    }

    /**
     * Checks, может ли user приглашать members.
     *
     * @param Community|null $team
     * @param User|null $viewer
     * @return bool
     */
    public function canInvite(?Community $team, ?User $viewer): bool
    {
        if (! $team || ! $viewer) {
            return false;
        }

        return in_array($this->role((int) $team->id, (int) $viewer->id), [1, 2, 3], true);
    }

    /**
     * Returns строковый type участия user.
     *
     * @param Community $team
     * @param User|null $viewer
     * @return string
     */
    public function membershipType(Community $team, ?User $viewer): string
    {
        return MembershipRole::membershipTypeFor($this->role((int) $team->id, $viewer?->id));
    }

    /**
     * Checks, является ли сообщество закрытым.
     *
     * @param Community $team
     * @return bool
     */
    public function isClosed(Community $team): bool
    {
        return (int) $this->settings($team)->type === CommunityPrivacyType::Closed->value;
    }

    /**
     * Checks, является ли сообщество приватным.
     *
     * @param Community $team
     * @return bool
     */
    public function isPrivate(Community $team): bool
    {
        return (int) $this->settings($team)->type === CommunityPrivacyType::Private->value;
    }

    /**
     * Checks, можно ли user видеть основной контент community.
     *
     * @param Community $team
     * @param User|null $viewer
     * @return bool
     */
    public function canViewCommunityContent(Community $team, ?User $viewer): bool
    {
        $role = $this->role((int) $team->id, $viewer?->id);

        if ($role === MembershipRole::Blocked->value) {
            return false;
        }

        if (! $this->isClosed($team)) {
            return true;
        }

        return in_array($role, [1, 2, 3], true);
    }

    /**
     * Меняет status участия user в entity.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $status
     * @return bool
     * @throws \Throwable
     */
    public function changeMembership(Community $team, User $viewer, int $status): bool
    {
        if (! in_array($status, [0, 1], true)) {
            return false;
        }

        return DB::transaction(function () use ($team, $viewer, $status): bool {
            /** @var CommunityRole|null $role */
            $role = CommunityRole::query()
                ->where('community_id', $team->id)
                ->where('user_id', $viewer->id)
                ->lockForUpdate()
                ->first();

            if ($status === 0) {
                if ($role && (int) $role->role === 1) {
                    return false;
                }

                return $role ? (bool) $role->delete() : false;
            }

            if ($role && (int) $role->role === 4) {
                return false;
            }

            if ($role && in_array((int) $role->role, [1, 2, 3], true)) {
                return true;
            }

            $memberRole = $role && (int) $role->role === 5
                ? 3
                : ((int) $this->settings($team)->type === 0 ? 3 : 0);

            if ($role) {
                $role->fill(['role' => $memberRole])->save();

                return true;
            }

            CommunityRole::query()->create([
                'community_id' => $team->id,
                'user_id' => $viewer->id,
                'role' => $memberRole,
            ]);

            return true;
        });
    }

    /**
     * Приглашает друзей user в selected сущность.
     *
     * @param Community $team
     * @param User $viewer
     * @return int
     */
    public function inviteFriends(Community $team, User $viewer): int
    {
        return $this->inviteFriendUsers($team, $viewer)->count();
    }

    /**
     * Returns друзей user, которых можно пригласить в selected сущность.
     *
     * @param Community $team
     * @param User $viewer
     * @return Collection
     */
    public function invitableFriends(Community $team, User $viewer): Collection
    {
        if (! $this->canInvite($team, $viewer)) {
            return collect();
        }

        $friendIds = $this->availableFriendIds($team, $viewer);

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
     * Приглашает selected друзей user и возвращает list приглашенных users.
     *
     * @param Community $team
     * @param User $viewer
     * @param array $friendIds
     * @return Collection
     */
    public function inviteFriendUsers(Community $team, User $viewer, array $friendIds = []): Collection
    {
        if (! $this->canInvite($team, $viewer)) {
            return collect();
        }

        $availableIds = $this->availableFriendIds($team, $viewer);
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

        $rows = $invitees->keys()->map(fn (int $id): array => [
            'community_id' => $team->id,
            'user_id' => $id,
            'role' => 5,
        ])->all();

        CommunityRole::query()->insert($rows);

        return $invitees->values();
    }

    /**
     * Deletes members из community с учетом прав current user.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @return bool
     * @throws \Throwable
     */
    public function removeMember(Community $team, User $viewer, int $userId): bool
    {
        return $this->changeManagedMemberRole($team, $viewer, $userId, null);
    }

    /**
     * Blocks members community с учетом прав current user.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @return bool
     * @throws \Throwable
     */
    public function blockMember(Community $team, User $viewer, int $userId): bool
    {
        return $this->changeManagedMemberRole($team, $viewer, $userId, MembershipRole::Blocked);
    }

    /**
     * Deletes user из черного списка community.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @return bool
     */
    public function removeBlockedMember(Community $team, User $viewer, int $userId): bool
    {
        if (! $this->canManage($team, $viewer) || $userId < 1) {
            return false;
        }

        return CommunityRole::query()
            ->where('community_id', $team->id)
            ->where('user_id', $userId)
            ->where('role', MembershipRole::Blocked->value)
            ->delete() > 0;
    }

    /**
     * Назначает user administrator community.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @return bool
     * @throws \Throwable
     */
    public function addAdmin(Community $team, User $viewer, int $userId): bool
    {
        if (! $this->isOwner($team, $viewer) || $userId < 1) {
            return false;
        }

        /** @var User|null $user */
        $user = User::query()
            ->whereKey($userId)
            ->where('status', UserStatus::Confirmed->value)
            ->first();

        if (! $user) {
            return false;
        }

        return DB::transaction(function () use ($team, $userId): bool {
            /** @var CommunityRole|null $role */
            $role = CommunityRole::query()
                ->where('community_id', $team->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($role && in_array((int) $role->role, [MembershipRole::Owner->value, MembershipRole::Blocked->value], true)) {
                return false;
            }

            CommunityRole::query()->updateOrCreate([
                'community_id' => $team->id,
                'user_id' => $userId,
            ], [
                'role' => MembershipRole::Admin->value,
            ]);

            return true;
        });
    }

    /**
     * Снимает с user role administrator и оставляет его memberом.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @return bool
     */
    public function removeAdmin(Community $team, User $viewer, int $userId): bool
    {
        if (! $this->isOwner($team, $viewer) || $userId < 1) {
            return false;
        }

        return CommunityRole::query()
            ->where('community_id', $team->id)
            ->where('user_id', $userId)
            ->where('role', MembershipRole::Admin->value)
            ->update(['role' => MembershipRole::Member->value]) > 0;
    }

    /**
     * Ищет users, которых owner может назначить administrators.
     *
     * @param Community $team
     * @param User $viewer
     * @param string $search
     * @param int $limit
     * @return Collection
     */
    public function searchAdminCandidates(Community $team, User $viewer, string $search, int $limit = 10): Collection
    {
        $search = trim($search);

        if (! $this->isOwner($team, $viewer) || $search === '') {
            return collect();
        }

        $query = User::query()
            ->where('status', UserStatus::Confirmed->value)
            ->whereNotIn('id', CommunityRole::query()
                ->select('user_id')
                ->where('community_id', $team->id)
                ->whereNotNull('user_id')
                ->whereIn('role', [
                    MembershipRole::Owner->value,
                    MembershipRole::Admin->value,
                    MembershipRole::Blocked->value,
                ]));

        $query->where(function (Builder $query) use ($search): void {
            if (ctype_digit($search)) {
                $query->orWhere('id', (int) $search);
            }

            $query
                ->orWhere('firstname', 'like', '%' . $search . '%')
                ->orWhere('lastname', 'like', '%' . $search . '%')
                ->orWhere('secondname', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE ?", ['%' . $search . '%'])
                ->orWhereRaw("CONCAT_WS(' ', lastname, firstname) LIKE ?", ['%' . $search . '%']);
        });

        $users = $query
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->limit(max(1, min($limit, 25)))
            ->get();

        if ($users->isEmpty()) {
            return collect();
        }

        $roles = CommunityRole::query()
            ->where('community_id', $team->id)
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('role', 'user_id');

        return $users->map(fn (User $user): array => [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
            'email' => (string) $user->email,
            'city' => (string) $user->city,
            'avatar' => FrontAssets::userAvatar($user),
            'role_name' => MembershipRole::labelFor($roles->has($user->id) ? (int) $roles->get($user->id) : null) ?: 'Not a member',
        ]);
    }

    /**
     * Меняет role управляемого members or deletes его из community.
     *
     * @param Community $team
     * @param User $viewer
     * @param int $userId
     * @param MembershipRole|null $newRole
     * @return bool
     * @throws \Throwable
     */
    private function changeManagedMemberRole(Community $team, User $viewer, int $userId, ?MembershipRole $newRole): bool
    {
        if (! $this->canManage($team, $viewer) || $userId < 1 || (int) $viewer->id === $userId) {
            return false;
        }

        return DB::transaction(function () use ($team, $viewer, $userId, $newRole): bool {
            /** @var CommunityRole|null $viewerRole */
            $viewerRole = CommunityRole::query()
                ->where('community_id', $team->id)
                ->where('user_id', $viewer->id)
                ->lockForUpdate()
                ->first();

            /** @var CommunityRole|null $targetRole */
            $targetRole = CommunityRole::query()
                ->where('community_id', $team->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $viewerRole || ! $targetRole) {
                return false;
            }

            $viewerRoleValue = (int) $viewerRole->role;
            $targetRoleValue = (int) $targetRole->role;

            if ($targetRoleValue === MembershipRole::Owner->value) {
                return false;
            }

            if ($viewerRoleValue === MembershipRole::Admin->value && $targetRoleValue !== MembershipRole::Member->value) {
                return false;
            }

            if (! in_array($targetRoleValue, [MembershipRole::Admin->value, MembershipRole::Member->value], true)) {
                return false;
            }

            if ($newRole === null) {
                return (bool) $targetRole->delete();
            }

            $targetRole->fill(['role' => $newRole->value])->save();

            return true;
        });
    }

    /**
     * Returns id друзей, у которых еще no роли в выбранной entity.
     *
     * @param Community $team
     * @param User $viewer
     * @return Collection
     */
    private function availableFriendIds(Community $team, User $viewer): Collection
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

        $existingIds = CommunityRole::query()
            ->where('community_id', $team->id)
            ->whereIn('user_id', $friendIds)
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id);

        return $friendIds->diff($existingIds)->values();
    }

    /**
     * Returns набор прав user для current entity.
     *
     * @param Community $team
     * @param User|null $viewer
     * @return array
     */
    public function permissions(Community $team, ?User $viewer): array
    {
        $settings = $this->settings($team);
        $role = $this->role((int) $team->id, $viewer?->id);

        return [
            'wall' => $this->permissionAllows((int) $settings->permission_wall, $role, true),
            'photo' => $this->permissionAllows((int) $settings->permission_photo, $role, false),
            'video' => $this->permissionAllows((int) $settings->permission_video, $role, false),
        ];
    }

    /**
     * Checks, можно ли текущему user видеть выбранный section community.
     *
     * @param Community|null $team
     * @param User|null $viewer
     * @param string $section
     * @return bool
     */
    public function canViewSection(?Community $team, ?User $viewer, string $section): bool
    {
        if (! $team || ! $this->canViewCommunityContent($team, $viewer)) {
            return false;
        }

        $permissions = $this->permissions($team, $viewer);

        return (bool) ($permissions[$section] ?? true);
    }

    /**
     * Returns or creates настройки community.
     */
    public function settings(Community $team): CommunitySetting
    {
        /** @var CommunitySetting $settings */
        $settings = CommunitySetting::query()->firstOrCreate([
            'community_id' => $team->id,
        ], [
            'permission_wall' => 0,
            'permission_photo' => 0,
            'permission_video' => 0,
            'type' => 0,
        ]);

        return $settings;
    }

    /**
     * Creates team и ее настройки для владельца.
     *
     * @param User $owner
     * @param CommunityData $data
     * @return Community
     * @throws \Throwable
     */
    public function createTeam(User $owner, CommunityData $data): Community
    {
        return DB::transaction(function () use ($owner, $data): Community {
            $avatar = $this->images->storeCommunityImage($data->avatarFile, 'avatar');
            $cover = $this->images->storeCommunityImage($data->coverFile, 'cover_page');

            /** @var Community $team */
            $team = $this->model->newQuery()->create([
                'type' => 'team',
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'sport_type' => $data->sportType ?: $this->sportName($data->sportId),
                'avatar' => $avatar ?? '',
                'cover_page' => $cover ?? '',
                'status' => CommunityStatus::Confirmed->value,
            ]);

            CommunityRole::query()->create([
                'community_id' => $team->id,
                'user_id' => $owner->id,
                'role' => 1,
            ]);

            CommunitySetting::query()->create([
                'community_id' => $team->id,
                'permission_wall' => 0,
                'permission_photo' => 0,
                'permission_video' => 0,
                'type' => 0,
            ]);

            $this->syncGeoTarget($team->type, (int) $team->id, $data->cityId);

            return $team;
        });
    }

    /**
     * Creates group и ее настройки для владельца.
     *
     * @param User $owner
     * @param CommunityData $data
     * @return Community
     * @throws \Throwable
     */
    public function createGroup(User $owner, CommunityData $data): Community
    {
        return DB::transaction(function () use ($owner, $data): Community {
            $avatar = $this->images->storeCommunityImage($data->avatarFile, 'avatar', 'group');
            $cover = $this->images->storeCommunityImage($data->coverFile, 'cover_page', 'group');

            /** @var Community $group */
            $group = $this->model->newQuery()->create([
                'type' => 'group',
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'sport_type' => $data->sportType ?: $this->sportName($data->sportId),
                'avatar' => $avatar ?? '',
                'cover_page' => $cover ?? '',
                'status' => CommunityStatus::Confirmed->value,
            ]);

            CommunityRole::query()->create([
                'community_id' => $group->id,
                'user_id' => $owner->id,
                'role' => 1,
            ]);

            CommunitySetting::query()->create([
                'community_id' => $group->id,
                'permission_wall' => 0,
                'permission_photo' => 0,
                'permission_video' => 0,
                'type' => 0,
            ]);

            $this->syncGeoTarget($group->type, (int) $group->id, $data->cityId);

            return $group;
        });
    }

    /**
     * Updates team, настройки и image.
     *
     * @param Community $team
     * @param CommunityData $data
     * @return bool
     * @throws \Throwable
     */
    public function updateTeam(Community $team, CommunityData $data): bool
    {
        return DB::transaction(function () use ($team, $data): bool {
            $oldAvatar = (string) $team->avatar;
            $oldCover = (string) $team->cover_page;
            $avatar = $this->images->storeCommunityImage($data->avatarFile, 'avatar');
            $cover = $this->images->storeCommunityImage($data->coverFile, 'cover_page');
            $fields = [
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'sport_type' => $data->sportType ?: $this->sportName($data->sportId),
            ];

            if ($avatar) {
                $fields['avatar'] = $avatar;
            }

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $team->fill($fields)->save();

            if ($avatar && $oldAvatar) {
                $this->images->deleteCommunityImage($oldAvatar, 'avatar');
            }

            if ($cover && $oldCover) {
                $this->images->deleteCommunityImage($oldCover, 'cover_page');
            }

            $this->settings($team)->fill([
                'permission_wall' => $data->permissionWall,
                'permission_photo' => $data->permissionPhoto,
                'permission_video' => $data->permissionVideo,
                'type' => $data->type,
            ])->save();

            $this->syncGeoTarget($team->type, (int) $team->id, $data->cityId);

            return true;
        });
    }

    /**
     * Updates group, настройки и image.
     *
     * @param Community $group
     * @param CommunityData $data
     * @return bool
     * @throws \Throwable
     */
    public function updateGroup(Community $group, CommunityData $data): bool
    {
        return DB::transaction(function () use ($group, $data): bool {
            $oldAvatar = (string) $group->avatar;
            $oldCover = (string) $group->cover_page;
            $avatar = $this->images->storeCommunityImage($data->avatarFile, 'avatar', 'group');
            $cover = $this->images->storeCommunityImage($data->coverFile, 'cover_page', 'group');
            $fields = [
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'sport_type' => $data->sportType ?: $this->sportName($data->sportId),
            ];

            if ($avatar) {
                $fields['avatar'] = $avatar;
            }

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $group->fill($fields)->save();

            if ($avatar && $oldAvatar) {
                $this->images->deleteCommunityImage($oldAvatar, 'avatar', 'group');
            }

            if ($cover && $oldCover) {
                $this->images->deleteCommunityImage($oldCover, 'cover_page', 'group');
            }

            $this->settings($group)->fill([
                'permission_wall' => $data->permissionWall,
                'permission_photo' => $data->permissionPhoto,
                'permission_video' => $data->permissionVideo,
                'type' => $data->type,
            ])->save();

            $this->syncGeoTarget($group->type, (int) $group->id, $data->cityId);

            return true;
        });
    }

    /**
     * Returns name city по его идентификатору.
     */
    public function cityName(?int $cityId): string
    {
        if (! $cityId) {
            return '';
        }

        return (string) (GeoCity::query()->find($cityId)?->name_ru ?? '');
    }

    /**
     * Returns name sport type по идентификатору.
     */
    public function sportName(?int $sportId): string
    {
        if (! $sportId) {
            return '';
        }

        return (string) (SportType::query()->find($sportId)?->name ?? '');
    }

    /**
     * Преобразует team в массив data для карточки.
     */
    public function serializeTeam(Community $team): array
    {
        $settings = $team->settings ?: $this->settings($team);
        $privacyType = (int) $settings->type;
        $membersCount = (int) ($team->members_count ?? $team->roles()->whereIn('role', [1, 2, 3])->count());

        return [
            'id' => (int) $team->id,
            'name' => (string) $team->name,
            'about' => (string) $team->about,
            'place' => (string) $team->place,
            'sport_type' => (string) $team->sport_type,
            'privacy_type' => $privacyType,
            'is_private' => $privacyType === CommunityPrivacyType::Private->value,
            'is_closed' => $privacyType === CommunityPrivacyType::Closed->value,
            'type_label' => CommunityPrivacyType::labelFor($privacyType, 'team'),
            'avatar' => FrontAssets::communityAvatar($team),
            'cover' => FrontAssets::communityCover($team),
            'members_count' => $membersCount,
            'members_text' => $membersCount . ' members',
        ];
    }

    /**
     * Преобразует group в массив data для карточки.
     *
     * @param Community $group
     * @return array
     */
    public function serializeGroup(Community $group): array
    {
        $settings = $group->settings ?: $this->settings($group);
        $privacyType = (int) $settings->type;
        $membersCount = (int) ($group->members_count ?? $group->roles()->whereIn('role', [1, 2, 3])->count());

        return [
            'id' => (int) $group->id,
            'name' => (string) $group->name,
            'about' => (string) $group->about,
            'place' => (string) $group->place,
            'sport_type' => (string) $group->sport_type,
            'privacy_type' => $privacyType,
            'is_private' => $privacyType === CommunityPrivacyType::Private->value,
            'is_closed' => $privacyType === CommunityPrivacyType::Closed->value,
            'type_label' => CommunityPrivacyType::labelFor($privacyType, 'group'),
            'avatar' => FrontAssets::communityAvatar($group),
            'cover' => FrontAssets::communityCover($group),
            'members_count' => $membersCount,
            'members_text' => $membersCount . ' members',
        ];
    }

    /**
     * Преобразует members community в массив data.
     *
     * @param CommunityRole $role
     * @return array|null
     */
    private function serializeMember(CommunityRole $role, ?int $viewerId = null): ?array
    {
        $user = $role->user;

        if (! $user || ! $user->isActive()) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
            'firstname' => (string) $user->firstname,
            'lastname' => (string) $user->lastname,
            'avatar' => FrontAssets::userAvatar($user),
            'city' => (string) $user->city,
            'role' => (int) $role->role,
            'role_name' => MembershipRole::labelFor((int) $role->role),
            'friendship_status' => $this->friends->friendshipStatus($viewerId, (int) $user->id),
            'is_online' => false,
        ];
    }

    /**
     * Преобразует event в массив data для output.
     *
     * @param Event $event
     * @param string $participantType
     * @return array
     */
    private function serializeEvent(Event $event, string $participantType = 'team'): array
    {
        return [
            'id' => (int) $event->id,
            'name' => (string) $event->name,
            'avatar' => FrontAssets::eventAvatar($event),
            'sport_type' => $event->sportType?->name ?: (string) $event->sport_type,
            'city' => (string) $event->place,
            'date' => $event->date_from?->format('d.m.Y H:i') ?? '',
            'date_to' => $event->date_to?->format('d.m.Y H:i') ?? '',
            'description' => (string) $event->description,
            'participants' => AcceptedEventMember::query()
                ->where('event_id', $event->id)
                ->where('eventable_type', $participantType)
                ->count(),
            'user_participants' => AcceptedEventMember::query()
                ->where('event_id', $event->id)
                ->where('eventable_type', 'user')
                ->count(),
            'active' => ! $event->date_to || $event->date_to->isFuture(),
        ];
    }

    /**
     * Checks, разрешает ли настройка приватности selected действие.
     *
     * @param int $permission
     * @param int|null $role
     * @param bool $isWall
     * @return bool
     */
    private function permissionAllows(int $permission, ?int $role, bool $isWall): bool
    {
        if ($role === 4 || $permission === 1) {
            return false;
        }

        if ($permission === 2) {
            return in_array($role, [1, 2, 3], true);
        }

        if ($isWall && $permission === 3) {
            return in_array($role, [1, 2], true);
        }

        return true;
    }

    /**
     * Применяет фильтры поиска к запросу сообществ.
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyCommunityFilters(Builder $query, array $filters): void
    {
        $place = trim((string) ($filters['place'] ?? ''));
        $sport = trim((string) ($filters['sport'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        if ($place === '' && (int) ($filters['id_place'] ?? 0) > 0) {
            $place = $this->cityName((int) $filters['id_place']);
        }

        if ($sport === '' && (int) ($filters['id_sport'] ?? 0) > 0) {
            $sport = $this->sportName((int) $filters['id_sport']);
        }

        if ($place !== '') {
            $query->where('communities.place', 'like', '%' . $place . '%');
        }

        if ($sport !== '') {
            $query->where('communities.sport_type', 'like', '%' . $sport . '%');
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('communities.name', 'like', '%' . $search . '%')
                    ->orWhere('communities.about', 'like', '%' . $search . '%')
                    ->orWhere('communities.place', 'like', '%' . $search . '%')
                    ->orWhere('communities.sport_type', 'like', '%' . $search . '%');
            });
        }
    }

    /**
     * Скрывает приватные community от users, которые не являются их members.
     *
     * @param Builder $query
     * @param User|null $viewer
     * @return void
     */
    private function applyPrivateVisibility(Builder $query, ?User $viewer): void
    {
        $query->where(function (Builder $query) use ($viewer): void {
            $query
                ->whereDoesntHave('settings')
                ->orWhereHas('settings', fn (Builder $settings): Builder => $settings
                    ->where('type', '!=', CommunityPrivacyType::Private->value));

            if ($viewer) {
                $query->orWhereHas('roles', fn (Builder $roles): Builder => $roles
                    ->where('user_id', $viewer->id)
                    ->whereIn('role', [1, 2, 3]));
            }
        });
    }

}
