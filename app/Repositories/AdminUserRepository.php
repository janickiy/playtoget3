<?php

namespace App\Repositories;

use App\DTO\Admin\UserData;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminUserRepository extends BaseRepository
{
    /**
     * Connects user model for admin operations.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates user from DTO and hashes password, if provided.
     */
    public function createFromData(UserData $data): Builder|Model
    {
        $payload = $this->payload($data);
        $payload['password'] = Hash::make((string) $data->password);

        return $this->create($payload);
    }

    /**
     * Updates user from DTO and changes password only when the field is filled in.
     */
    public function updateFromData(UserData $data): bool
    {
        $payload = $this->payload($data);

        if ($data->password !== null) {
            $payload['password'] = Hash::make($data->password);
        }

        return $this->update($data->id, $payload);
    }

    /**
     * Blocks or unblocks one user.
     */
    public function setBlocked(int $id, bool $blocked): bool
    {
        $payload = [
            'status' => $blocked ? UserStatus::Blocked->value : UserStatus::Confirmed->value,
        ];

        if (! $blocked) {
            $payload['confirmed_at'] = Carbon::now();
        }

        return $this->update($id, $payload);
    }

    /**
     * Marks one user as deleted without physical deletion of associated data.
     */
    public function markDeleted(int $id): bool
    {
        return $this->update($id, ['status' => UserStatus::Deleted->value]);
    }

    /**
     * Runs mass blocking, unblocking or deletion of selected users.
     *
     * @param array<int, int|string> $ids
     */
    public function bulkAction(string $action, array $ids): int
    {
        $ids = $this->normalizeIds($ids);

        return match ($action) {
            'block' => $this->bulkSetBlocked($ids, true),
            'unblock' => $this->bulkSetBlocked($ids, false),
            'delete' => $this->bulkMarkDeleted($ids),
            default => 0,
        };
    }

    /**
     * Massively changes the block selected users flag.
     *
     * @param array<int, int> $ids
     */
    public function bulkSetBlocked(array $ids, bool $blocked): int
    {
        $payload = [
            'status' => $blocked ? UserStatus::Blocked->value : UserStatus::Confirmed->value,
        ];

        if (! $blocked) {
            $payload['confirmed_at'] = Carbon::now();
        }

        return $this->model->newQuery()
            ->whereKey($ids)
            ->update($payload);
    }

    /**
     * Marks selected users as deleted in bulk.
     *
     * @param array<int, int> $ids
     */
    public function bulkMarkDeleted(array $ids): int
    {
        return $this->model->newQuery()
            ->whereKey($ids)
            ->update(['status' => UserStatus::Deleted->value]);
    }

    /**
     * Prepares an array of user attributes for saving.
     *
     * @return array<string, mixed>
     */
    private function payload(UserData $data): array
    {
        $payload = $data->toArray();

        if ((int) $payload['status'] === UserStatus::Confirmed->value && empty($payload['confirmed_at'])) {
            $payload['confirmed_at'] = Carbon::now();
        }

        if ((int) $payload['status'] !== UserStatus::Confirmed->value) {
            $payload['confirmed_at'] = null;
        }

        return $payload;
    }

    /**
     * Casts list id to unique positive integer values.
     *
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
