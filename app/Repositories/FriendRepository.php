<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FriendRepository extends BaseRepository
{
    public function __construct(Friend $model)
    {
        parent::__construct($model);
    }

    public function possibleFriendsFor(int $userId, int $limit = 6, array $filters = []): Collection
    {
        $excludedIds = $this->relationUserIds($userId)->all();

        return $this->activeUsersQuery($filters)
            ->where('id', '!=', $userId)
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function friendsFor(int $userId, int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        return $this->usersFromRelations($this->friendRelations($userId), $userId, $filters)
            ->slice($offset, $limit)
            ->values();
    }

    public function friendsCountFor(int $userId, array $filters = []): int
    {
        return $this->usersFromRelations($this->friendRelations($userId), $userId, $filters)->count();
    }

    public function incomingRequestsFor(int $userId, int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        $relations = $this->model->newQuery()
            ->where('status', 0)
            ->where('friend_id', $userId)
            ->orderByDesc('added')
            ->orderByDesc('id')
            ->get();

        return $this->usersByOrderedIds($relations->pluck('user_id')->unique()->values(), $filters)
            ->slice($offset, $limit)
            ->values();
    }

    public function incomingRequestsCountFor(int $userId, array $filters = []): int
    {
        return $this->incomingRequestsFor($userId, PHP_INT_MAX, 0, $filters)->count();
    }

    public function outgoingRequestsFor(int $userId, int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        $relations = $this->model->newQuery()
            ->where('status', 0)
            ->where('user_id', $userId)
            ->orderByDesc('added')
            ->orderByDesc('id')
            ->get();

        return $this->usersByOrderedIds($relations->pluck('friend_id')->unique()->values(), $filters)
            ->slice($offset, $limit)
            ->values();
    }

    public function outgoingRequestsCountFor(int $userId, array $filters = []): int
    {
        return $this->outgoingRequestsFor($userId, PHP_INT_MAX, 0, $filters)->count();
    }

    public function requestFriendship(int $userId, int $friendId): ?int
    {
        if ($userId === $friendId) {
            return null;
        }

        /** @var Friend|null $relation */
        $relation = $this->relationBetween($userId, $friendId)->first();

        if (! $relation) {
            $this->model->newQuery()->create([
                'user_id' => $userId,
                'friend_id' => $friendId,
                'status' => 0,
                'added' => now(),
            ]);

            return 0;
        }

        if ((int) $relation->status === 1) {
            return 1;
        }

        if ((int) $relation->user_id === $friendId && (int) $relation->friend_id === $userId) {
            $relation->fill([
                'status' => 1,
                'added' => now(),
            ])->save();

            return 1;
        }

        $relation->fill([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 0,
            'added' => now(),
        ])->save();

        return 0;
    }

    public function acceptFriendship(int $userId, int $friendId): ?int
    {
        if ($userId === $friendId) {
            return null;
        }

        /** @var Friend|null $relation */
        $relation = $this->model->newQuery()
            ->where('user_id', $friendId)
            ->where('friend_id', $userId)
            ->first();

        if (! $relation) {
            $relation = $this->relationBetween($userId, $friendId)->first();
        }

        if (! $relation) {
            return null;
        }

        $relation->fill([
            'status' => 1,
            'added' => now(),
        ])->save();

        return 1;
    }

    public function removeFriendship(int $userId, int $friendId): bool
    {
        return $this->relationBetween($userId, $friendId)->delete() > 0;
    }

    public function friendshipStatus(?int $userId, int $friendId): string
    {
        if (! $userId || $userId === $friendId) {
            return '';
        }

        /** @var Friend|null $relation */
        $relation = $this->relationBetween($userId, $friendId)->first();

        if (! $relation) {
            return 'nofriend';
        }

        if ((int) $relation->status === 1) {
            return 'friend';
        }

        if ((int) $relation->status === 2) {
            return (int) $relation->user_id === $userId ? 'block' : 'blocked_by_user';
        }

        return (int) $relation->user_id === $userId ? 'invitation_sent' : 'invated';
    }

    public function blockUser(int $userId, int $friendId): bool
    {
        if ($userId === $friendId) {
            return false;
        }

        $this->relationBetween($userId, $friendId)->delete();

        $this->model->newQuery()->create([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 2,
            'added' => now(),
        ]);

        return true;
    }

    public function unblockUser(int $userId, int $friendId): bool
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('friend_id', $friendId)
            ->where('status', 2)
            ->delete() > 0;
    }

    public function serializeUsers(Collection $users, ?int $senderId = null): array
    {
        return $users
            ->map(fn (User $user): array => [
                'user_id' => $user->id,
                'avatar' => FrontAssets::userAvatar($user),
                'firstname' => $user->firstname ?: $user->displayName(),
                'lastname' => $user->firstname ? (string) $user->lastname : '',
                'city' => $user->city,
                'status_user' => 'offline',
                'sender_id' => $senderId,
            ])
            ->values()
            ->all();
    }

    private function friendRelations(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where('status', 1)
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->orderByDesc('added')
            ->orderByDesc('id')
            ->get();
    }

    private function usersFromRelations(Collection $relations, int $userId, array $filters = []): Collection
    {
        $ids = $relations
            ->map(fn (Friend $relation): ?int => match ($userId) {
                (int) $relation->user_id => (int) $relation->friend_id,
                (int) $relation->friend_id => (int) $relation->user_id,
                default => null,
            })
            ->filter()
            ->unique()
            ->values();

        return $this->usersByOrderedIds($ids, $filters);
    }

    private function relationUserIds(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->get(['user_id', 'friend_id'])
            ->flatMap(fn (Friend $relation): array => [
                (int) $relation->user_id,
                (int) $relation->friend_id,
            ])
            ->reject(fn (int $id): bool => $id === $userId)
            ->unique()
            ->values();
    }

    private function usersByOrderedIds(Collection $ids, array $filters = []): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $users = $this->activeUsersQuery($filters)
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        return $ids
            ->map(fn (int $id): ?User => $users->get($id))
            ->filter()
            ->values();
    }

    private function activeUsersQuery(array $filters = []): Builder
    {
        return User::query()
            ->where('confirmed', true)
            ->where('banned', false)
            ->where('deleted', false)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                foreach (preg_split('/\s+/', $search) ?: [] as $term) {
                    $term = trim($term);

                    if ($term === '') {
                        continue;
                    }

                    $query->where(function (Builder $query) use ($term): void {
                        $query
                            ->where('firstname', 'like', '%' . $term . '%')
                            ->orWhere('lastname', 'like', '%' . $term . '%')
                            ->orWhere('email', 'like', '%' . $term . '%');
                    });
                }
            })
            ->when($filters['sex'] ?? null, fn (Builder $query, string $sex) => $query->where('sex', $sex))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', 'like', '%' . $city . '%'))
            ->when($filters['min_age'] ?? null, fn (Builder $query, int $age) => $query->whereDate('birthday', '<=', now()->subYears($age)->toDateString()))
            ->when($filters['max_age'] ?? null, fn (Builder $query, int $age) => $query->whereDate('birthday', '>=', now()->subYears($age + 1)->addDay()->toDateString()));
    }

    private function relationBetween(int $userId, int $friendId): Builder
    {
        return $this->model->newQuery()
            ->where(function (Builder $query) use ($userId, $friendId): void {
                $query
                    ->where(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $userId)->where('friend_id', $friendId);
                    })
                    ->orWhere(function (Builder $query) use ($userId, $friendId): void {
                        $query->where('user_id', $friendId)->where('friend_id', $userId);
                    });
            });
    }
}
