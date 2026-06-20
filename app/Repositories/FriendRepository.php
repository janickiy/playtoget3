<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Helpers\FrontAssets;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FriendRepository extends BaseRepository
{
    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(Friend $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns users, which can be proposed to friends.
     *
     * @param int $userId
     * @param int $limit
     * @param array $filters
     * @return Collection
     */
    public function possibleFriendsFor(int $userId, int $limit = 6, array $filters = []): Collection
    {
        $excludedIds = $this->relationUserIds($userId)->all();

        return $this->activeUsersQuery($filters)
            ->where('id', '!=', $userId)
            ->when($excludedIds !== [], fn(Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Returns of user friends with pagination and filters.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
    public function friendsFor(int $userId, int $limit = 10, int $offset = 0, array $filters = []): Collection
    {
        return $this->usersFromRelations($this->friendRelations($userId), $userId, $filters)
            ->slice($offset, $limit)
            ->values();
    }

    /**
     * Counts the user's friends taking into account filters.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function friendsCountFor(int $userId, array $filters = []): int
    {
        return $this->usersFromRelations($this->friendRelations($userId), $userId, $filters)->count();
    }

    /**
     * Returns incoming requests to friends with pagination and filters.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
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

    /**
     * Counts incoming requests to friends taking into account filters.
     *
     * @param int $userId
     * @param array $filters
     * @return int
     */
    public function incomingRequestsCountFor(int $userId, array $filters = []): int
    {
        return $this->incomingRequestsFor($userId, PHP_INT_MAX, 0, $filters)->count();
    }

    /**
     * Counts unique incoming requests in friends for the counter in the header.
     *
     * @param int $userId
     * @return int
     */
    public function incomingRequestsBadgeCountFor(int $userId): int
    {
        return $this->model->newQuery()
            ->where('status', 0)
            ->where('friend_id', $userId)
            ->whereNotNull('user_id')
            ->whereHas('user', fn (Builder $query): Builder => $query->where('status', UserStatus::Confirmed->value))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Returns outgoing requests to friends with pagination and filters.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return Collection
     */
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

    /**
     * Counts outgoing requests to friends taking into account filters.
     */
    public function outgoingRequestsCountFor(int $userId, array $filters = []): int
    {
        return $this->outgoingRequestsFor($userId, PHP_INT_MAX, 0, $filters)->count();
    }

    /**
     * Creates or updates an application in friends.
     *
     * @param int $userId
     * @param int $friendId
     * @return int|null
     */
    public function requestFriendship(int $userId, int $friendId): ?int
    {
        if ($userId === $friendId) {
            return null;
        }

        /** @var Friend|null $relation */
        $relation = $this->relationBetween($userId, $friendId)->first();

        if (!$relation) {
            $this->model->newQuery()->create([
                'user_id' => $userId,
                'friend_id' => $friendId,
                'status' => 0,
                'added' => now(),
            ]);

            return 0;
        }

        if ((int)$relation->status === 1) {
            return 1;
        }

        if ((int)$relation->status === 2) {
            return null;
        }

        if ((int)$relation->user_id === $friendId && (int)$relation->friend_id === $userId) {
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

    /**
     * Accepts an incoming request for friends and transfers the relationship to friendship status.
     *
     * @param int $userId
     * @param int $friendId
     * @return int|null
     */
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

        if (!$relation) {
            $relation = $this->relationBetween($userId, $friendId)->first();
        }

        if (!$relation) {
            return null;
        }

        if ((int)$relation->status === 2) {
            return null;
        }

        $relation->fill([
            'status' => 1,
            'added' => now(),
        ])->save();

        return 1;
    }

    /**
     * Deletes or cancels relationship friendship between users.
     *
     * @param int $userId
     * @param int $friendId
     * @return bool
     */
    public function removeFriendship(int $userId, int $friendId): bool
    {
        /** @var Friend|null $relation */
        $relation = $this->relationBetween($userId, $friendId)->first();

        if (! $relation) {
            return false;
        }

        if ((int) $relation->status === 2 && (int) $relation->user_id !== $userId) {
            return false;
        }

        return (bool) $relation->delete();
    }

    /**
     * Detects friendship status between two users.
     *
     * @param int|null $userId
     * @param int $friendId
     * @return string
     */
    public function friendshipStatus(?int $userId, int $friendId): string
    {
        if (!$userId || $userId === $friendId) {
            return '';
        }

        /** @var Friend|null $relation */
        $relation = $this->relationBetween($userId, $friendId)->first();

        if (!$relation) {
            return 'nofriend';
        }

        if ((int)$relation->status === 1) {
            return 'friend';
        }

        if ((int)$relation->status === 2) {
            return (int)$relation->user_id === $userId ? 'block' : 'blocked_by_user';
        }

        return (int)$relation->user_id === $userId ? 'invitation_sent' : 'invated';
    }

    /**
     * Blocks user in friendship relationships.
     *
     * @param int $userId
     * @param int $friendId
     * @return bool
     */
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

    /**
     * Removes user blocks in friendship relationships.
     *
     * @param int $userId
     * @param int $friendId
     * @return bool
     */
    public function unblockUser(int $userId, int $friendId): bool
    {
        return $this->model->newQuery()
                ->where('user_id', $userId)
                ->where('friend_id', $friendId)
                ->where('status', 2)
                ->delete() > 0;
    }

    /**
     * Converts the users collection to an array of cards.
     *
     * @param Collection $users
     * @param int|null $senderId
     * @return array
     */
    public function serializeUsers(Collection $users, ?int $senderId = null): array
    {
        return $users
            ->map(fn(User $user): array => [
                'user_id' => $user->id,
                'avatar' => FrontAssets::userAvatar($user),
                'firstname' => $user->firstname ?: $user->displayName(),
                'lastname' => $user->firstname ? (string)$user->lastname : '',
                'city' => $user->city,
                'status_user' => $user->isOnline() ? 'online' : 'offline',
                'sender_id' => $senderId,
            ])
            ->values()
            ->all();
    }

    /**
     * Returns relationships friendship user.
     *
     * @param int $userId
     * @return Collection
     */
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

    /**
     * Gets users from friendship connections, taking into account filters.
     *
     * @param Collection $relations
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    private function usersFromRelations(Collection $relations, int $userId, array $filters = []): Collection
    {
        $ids = $relations
            ->map(fn(Friend $relation): ?int => match ($userId) {
                (int)$relation->user_id => (int)$relation->friend_id,
                (int)$relation->friend_id => (int)$relation->user_id,
                default => null,
            })
            ->filter()
            ->unique()
            ->values();

        return $this->usersByOrderedIds($ids, $filters);
    }

    /**
     * Returns the identifiers of users already associated with the user.
     *
     * @param int $userId
     * @return Collection
     */
    private function relationUserIds(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where('user_id', $userId)
                    ->orWhere('friend_id', $userId);
            })
            ->get(['user_id', 'friend_id'])
            ->flatMap(fn(Friend $relation): array => [
                (int)$relation->user_id,
                (int)$relation->friend_id,
            ])
            ->reject(fn(int $id): bool => $id === $userId)
            ->unique()
            ->values();
    }

    /**
     * Returns users in order of passed IDs.
     *
     * @param Collection $ids
     * @param array $filters
     * @return Collection
     */
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
            ->map(fn(int $id): ?User => $users->get($id))
            ->filter()
            ->values();
    }

    /**
     * Prepares a basic query for active users taking into account filters.
     *
     * @param array $filters
     * @return Builder
     */
    private function activeUsersQuery(array $filters = []): Builder
    {
        return User::query()
            ->with('activity')
            ->where('status', UserStatus::Confirmed->value)
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
            ->when($filters['sex'] ?? null, fn(Builder $query, string $sex) => $query->where('sex', $sex))
            ->when($filters['city'] ?? null, fn(Builder $query, string $city) => $query->where('city', 'like', '%' . $city . '%'))
            ->when($filters['min_age'] ?? null, fn(Builder $query, int $age) => $query->whereDate('birthday', '<=', now()->subYears($age)->toDateString()))
            ->when($filters['max_age'] ?? null, fn(Builder $query, int $age) => $query->whereDate('birthday', '>=', now()->subYears($age + 1)->addDay()->toDateString()));
    }

    /**
     * Prepares a relationship friendship request between two users.
     *
     * @param int $userId
     * @param int $friendId
     * @return Builder
     */
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
