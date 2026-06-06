<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\AcceptedEventMember;
use App\Models\Community;
use App\Models\CommunityRole;
use App\Models\CommunitySetting;
use App\Models\Event;
use App\Models\GeoCity;
use App\Models\GeoTarget;
use App\Models\SportType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommunityRepository extends BaseRepository
{
    public function __construct(Community $model)
    {
        parent::__construct($model);
    }

    public function teams(): Collection
    {
        return $this->model->newQuery()
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->where('banned', false)
            ->orderBy('name')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    public function findTeam(int $id): ?Community
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()
            ->with(['settings', 'roles.user'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->whereKey($id)
            ->first();

        return $community;
    }

    public function defaultTeam(?User $viewer = null): ?Community
    {
        if ($viewer) {
            $team = $this->model->newQuery()
                ->where('communities.type', 'team')
                ->where('communities.banned', false)
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
            ->where('banned', false)
            ->orderBy('id')
            ->first();
    }

    public function teamForAlbumOwner(int $ownerId): ?Community
    {
        return $this->findTeam($ownerId);
    }

    public function myTeams(int $userId, int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'team')
            ->where('communities.banned', false)
            ->orderBy('community_roles.role')
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    public function popularTeams(int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'team')
            ->where('banned', false)
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    public function members(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->whereIn('role', [1, 2, 3])
            ->orderBy('role')
            ->get()
            ->map(fn (CommunityRole $role): array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    public function applications(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 0)
            ->get()
            ->map(fn (CommunityRole $role): array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    public function admins(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 2)
            ->get()
            ->map(fn (CommunityRole $role): array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    public function blocked(int $teamId): Collection
    {
        return CommunityRole::query()
            ->with('user.activity')
            ->where('community_id', $teamId)
            ->where('role', 4)
            ->get()
            ->map(fn (CommunityRole $role): array => $this->serializeMember($role))
            ->filter(fn (?array $member): bool => (bool) $member)
            ->values();
    }

    public function events(int $teamId): Collection
    {
        return Event::query()
            ->with('sportType')
            ->where('banned', false)
            ->whereHas('acceptedMembers', fn ($query) => $query
                ->where('eventable_type', 'team')
                ->where('member_id', $teamId))
            ->orderByDesc('date_from')
            ->get()
            ->map(fn (Event $event): array => [
                'id' => (int) $event->id,
                'name' => (string) $event->name,
                'avatar' => FrontAssets::eventCover($event),
                'sport_type' => $event->sportType?->name ?: (string) $event->sport_type,
                'city' => (string) $event->place,
                'date' => $event->date_from?->format('d.m.Y H:i') ?? '',
                'description' => (string) $event->description,
                'participants' => AcceptedEventMember::query()
                    ->where('event_id', $event->id)
                    ->where('eventable_type', 'team')
                    ->count(),
                'active' => ! $event->date_to || $event->date_to->isFuture(),
            ]);
    }

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

    public function isOwner(?Community $team, ?User $viewer): bool
    {
        if (! $team) {
            return false;
        }

        return $this->role((int) $team->id, $viewer?->id) === 1;
    }

    public function canManage(?Community $team, ?User $viewer): bool
    {
        if (! $team) {
            return false;
        }

        return in_array($this->role((int) $team->id, $viewer?->id), [1, 2], true);
    }

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

    public function createTeam(User $owner, array $data): Community
    {
        return DB::transaction(function () use ($owner, $data): Community {
            /** @var Community $team */
            $team = $this->model->newQuery()->create([
                'type' => 'team',
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
                'avatar' => $data['avatar'] ?? '',
                'cover_page' => $data['cover_page'] ?? '',
                'banned' => false,
                'moderate' => true,
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

            $this->syncGeoTarget($team, (int) ($data['city_id'] ?? 0));

            return $team;
        });
    }

    public function updateTeam(Community $team, array $data): bool
    {
        return DB::transaction(function () use ($team, $data): bool {
            $team->fill([
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
            ])->save();

            $this->settings($team)->fill([
                'permission_wall' => (int) ($data['permission_wall'] ?? 0),
                'permission_photo' => (int) ($data['permission_photo'] ?? 0),
                'permission_video' => (int) ($data['permission_video'] ?? 0),
                'type' => (int) ($data['type'] ?? 0),
            ])->save();

            $this->syncGeoTarget($team, (int) ($data['city_id'] ?? 0));

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

    public function sportName(?int $sportId): string
    {
        if (! $sportId) {
            return '';
        }

        return (string) (SportType::query()->find($sportId)?->name ?? '');
    }

    public function serializeTeam(Community $team): array
    {
        return [
            'id' => (int) $team->id,
            'name' => (string) $team->name,
            'about' => (string) $team->about,
            'place' => (string) $team->place,
            'sport_type' => (string) $team->sport_type,
            'avatar' => FrontAssets::communityAvatar($team),
            'cover' => FrontAssets::communityCover($team),
            'members_count' => (int) ($team->members_count ?? $team->roles()->whereIn('role', [1, 2, 3])->count()),
        ];
    }

    private function serializeMember(CommunityRole $role): ?array
    {
        $user = $role->user;

        if (! $user || $user->banned || $user->deleted) {
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
            'role_name' => $this->roleName((int) $role->role),
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

    private function syncGeoTarget(Community $team, int $cityId): void
    {
        if ($cityId < 1) {
            return;
        }

        GeoTarget::query()->updateOrCreate([
            'target_type' => 'team',
            'target_id' => $team->id,
        ], [
            'city_id' => $cityId,
        ]);
    }
}
