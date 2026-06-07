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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function findGroup(int $id): ?Community
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()
            ->with(['settings', 'roles.user'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'group')
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

    public function invitedTeams(int $userId, int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'team')
            ->where('communities.banned', false)
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $team): array => $this->serializeTeam($team));
    }

    public function myGroups(int $userId, int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'group')
            ->where('communities.banned', false)
            ->orderBy('community_roles.role')
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    public function myGroupsCount(int $userId): int
    {
        return (int) $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->whereIn('community_roles.role', [1, 2, 3])
            ->where('communities.type', 'group')
            ->where('communities.banned', false)
            ->count(DB::raw('distinct communities.id'));
    }

    public function popularGroups(int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->where('type', 'group')
            ->where('banned', false)
            ->orderByDesc('members_count')
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    public function popularGroupsCount(): int
    {
        return (int) $this->model->newQuery()
            ->where('type', 'group')
            ->where('banned', false)
            ->count();
    }

    public function invitedGroups(int $userId, int $limit = 5, int $offset = 0): Collection
    {
        return $this->model->newQuery()
            ->with(['settings'])
            ->withCount(['roles as members_count' => fn ($query) => $query->whereIn('role', [1, 2, 3])])
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'group')
            ->where('communities.banned', false)
            ->orderBy('communities.name')
            ->offset($offset)
            ->limit($limit)
            ->select('communities.*')
            ->get()
            ->map(fn (Community $group): array => $this->serializeGroup($group));
    }

    public function invitedGroupsCount(int $userId): int
    {
        return (int) $this->model->newQuery()
            ->join('community_roles', 'community_roles.community_id', '=', 'communities.id')
            ->where('community_roles.user_id', $userId)
            ->where('community_roles.role', 5)
            ->where('communities.type', 'group')
            ->where('communities.banned', false)
            ->count(DB::raw('distinct communities.id'));
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

    public function roleLabel(?int $role): string
    {
        return $role === null ? '' : $this->roleName($role);
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
            $avatar = $this->storeCommunityImage($data['avatar_file'] ?? null, 'avatar');
            $cover = $this->storeCommunityImage($data['cover_file'] ?? null, 'cover_page');

            /** @var Community $team */
            $team = $this->model->newQuery()->create([
                'type' => 'team',
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
                'avatar' => $avatar ?? '',
                'cover_page' => $cover ?? '',
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

    public function createGroup(User $owner, array $data): Community
    {
        return DB::transaction(function () use ($owner, $data): Community {
            $avatar = $this->storeCommunityImage($data['avatar_file'] ?? null, 'avatar', 'group');
            $cover = $this->storeCommunityImage($data['cover_file'] ?? null, 'cover_page', 'group');

            /** @var Community $group */
            $group = $this->model->newQuery()->create([
                'type' => 'group',
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
                'avatar' => $avatar ?? '',
                'cover_page' => $cover ?? '',
                'banned' => false,
                'moderate' => true,
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

            $this->syncGeoTarget($group, (int) ($data['city_id'] ?? 0));

            return $group;
        });
    }

    public function updateTeam(Community $team, array $data): bool
    {
        return DB::transaction(function () use ($team, $data): bool {
            $oldAvatar = (string) $team->avatar;
            $oldCover = (string) $team->cover_page;
            $avatar = $this->storeCommunityImage($data['avatar_file'] ?? null, 'avatar');
            $cover = $this->storeCommunityImage($data['cover_file'] ?? null, 'cover_page');
            $fields = [
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
            ];

            if ($avatar) {
                $fields['avatar'] = $avatar;
            }

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $team->fill($fields)->save();

            if ($avatar && $oldAvatar) {
                $this->deleteCommunityImage($oldAvatar, 'avatar');
            }

            if ($cover && $oldCover) {
                $this->deleteCommunityImage($oldCover, 'cover_page');
            }

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

    public function updateGroup(Community $group, array $data): bool
    {
        return DB::transaction(function () use ($group, $data): bool {
            $oldAvatar = (string) $group->avatar;
            $oldCover = (string) $group->cover_page;
            $avatar = $this->storeCommunityImage($data['avatar_file'] ?? null, 'avatar', 'group');
            $cover = $this->storeCommunityImage($data['cover_file'] ?? null, 'cover_page', 'group');
            $fields = [
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'sport_type' => $data['sport_type'] ?? '',
            ];

            if ($avatar) {
                $fields['avatar'] = $avatar;
            }

            if ($cover) {
                $fields['cover_page'] = $cover;
            }

            $group->fill($fields)->save();

            if ($avatar && $oldAvatar) {
                $this->deleteCommunityImage($oldAvatar, 'avatar', 'group');
            }

            if ($cover && $oldCover) {
                $this->deleteCommunityImage($oldCover, 'cover_page', 'group');
            }

            $this->settings($group)->fill([
                'permission_wall' => (int) ($data['permission_wall'] ?? 0),
                'permission_photo' => (int) ($data['permission_photo'] ?? 0),
                'permission_video' => (int) ($data['permission_video'] ?? 0),
                'type' => (int) ($data['type'] ?? 0),
            ])->save();

            $this->syncGeoTarget($group, (int) ($data['city_id'] ?? 0));

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
            'type_label' => $this->communityTypeLabel((int) ($team->settings?->type ?? $this->settings($team)->type), 'team'),
            'avatar' => FrontAssets::communityAvatar($team),
            'cover' => FrontAssets::communityCover($team),
            'members_count' => (int) ($team->members_count ?? $team->roles()->whereIn('role', [1, 2, 3])->count()),
            'members_text' => ((int) ($team->members_count ?? $team->roles()->whereIn('role', [1, 2, 3])->count())) . ' участников',
        ];
    }

    public function serializeGroup(Community $group): array
    {
        return [
            'id' => (int) $group->id,
            'name' => (string) $group->name,
            'about' => (string) $group->about,
            'place' => (string) $group->place,
            'sport_type' => (string) $group->sport_type,
            'type_label' => $this->communityTypeLabel((int) ($group->settings?->type ?? $this->settings($group)->type), 'group'),
            'avatar' => FrontAssets::communityAvatar($group),
            'cover' => FrontAssets::communityCover($group),
            'members_count' => (int) ($group->members_count ?? $group->roles()->whereIn('role', [1, 2, 3])->count()),
            'members_text' => ((int) ($group->members_count ?? $group->roles()->whereIn('role', [1, 2, 3])->count())) . ' участников',
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

    private function communityTypeLabel(int $type, string $kind = 'team'): string
    {
        $noun = $kind === 'group' ? 'группа' : 'команда';

        return match ($type) {
            1 => 'Приватная ' . $noun,
            2 => 'Закрытая ' . $noun,
            default => 'Открытая ' . $noun,
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

    private function syncGeoTarget(Community $community, int $cityId): void
    {
        if ($cityId < 1) {
            return;
        }

        GeoTarget::query()->updateOrCreate([
            'target_type' => $community->type,
            'target_id' => $community->id,
        ], [
            'city_id' => $cityId,
        ]);
    }

    private function storeCommunityImage(?UploadedFile $file, string $directory, string $kind = 'team'): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return Storage::disk('public')->putFileAs('images/' . $kind . 'content/' . $directory, $file, $filename)
            ? $filename
            : null;
    }

    private function deleteCommunityImage(string $filename, string $directory, string $kind = 'team'): void
    {
        Storage::disk('public')->delete('images/' . $kind . 'content/' . $directory . '/' . $filename);

        $legacyPath = public_path('uploads/images/' . $kind . 'content/' . $directory . '/' . $filename);
        if (is_file($legacyPath)) {
            @unlink($legacyPath);
        }
    }
}
