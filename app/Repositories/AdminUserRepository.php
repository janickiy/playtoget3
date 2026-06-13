<?php

namespace App\Repositories;

use App\DTO\Admin\UserData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminUserRepository extends BaseRepository
{
    /**
     * Подключает модель пользователя для админских операций.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Создает пользователя из DTO и хеширует пароль, если он передан.
     */
    public function createFromData(UserData $data): Builder|Model
    {
        $payload = $this->payload($data);
        $payload['password'] = Hash::make((string) $data->password);

        return $this->create($payload);
    }

    /**
     * Обновляет пользователя из DTO и меняет пароль только при заполненном поле.
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
     * Блокирует или разблокирует одного пользователя.
     */
    public function setBlocked(int $id, bool $blocked): bool
    {
        return $this->update($id, ['banned' => $blocked]);
    }

    /**
     * Помечает одного пользователя как удаленного без физического удаления связанных данных.
     */
    public function markDeleted(int $id): bool
    {
        return $this->update($id, ['deleted' => true]);
    }

    /**
     * Выполняет массовую блокировку, разблокировку или удаление выбранных пользователей.
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
     * Массово меняет флаг блокировки выбранных пользователей.
     *
     * @param array<int, int> $ids
     */
    public function bulkSetBlocked(array $ids, bool $blocked): int
    {
        return $this->model->newQuery()
            ->whereKey($ids)
            ->update(['banned' => $blocked]);
    }

    /**
     * Массово помечает выбранных пользователей как удаленных.
     *
     * @param array<int, int> $ids
     */
    public function bulkMarkDeleted(array $ids): int
    {
        return $this->model->newQuery()
            ->whereKey($ids)
            ->update(['deleted' => true]);
    }

    /**
     * Готовит массив атрибутов пользователя для сохранения.
     *
     * @return array<string, mixed>
     */
    private function payload(UserData $data): array
    {
        return $data->toArray();
    }

    /**
     * Приводит список id к уникальным положительным целым значениям.
     *
     * @param array<int, int|string> $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
