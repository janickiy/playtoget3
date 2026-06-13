<?php

namespace App\Repositories;

use App\DTO\Admin\CommunityData;
use App\Enums\CommunityStatus;
use App\Models\Community;

class AdminCommunityRepository extends BaseRepository
{
    /**
     * Подключает модель комьюнити, с которой работает админский репозиторий.
     */
    public function __construct(Community $model)
    {
        parent::__construct($model);
    }

    /**
     * Возвращает варианты типов комьюнити для формы админки.
     *
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return [
            'team' => 'Команда',
            'group' => 'Группа',
        ];
    }

    /**
     * Возвращает подпись типа комьюнити.
     */
    public function typeLabel(?string $type): string
    {
        return $this->typeOptions()[$type] ?? (string) $type;
    }

    /**
     * Возвращает варианты статусов комьюнити для формы админки.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return CommunityStatus::options();
    }

    /**
     * Возвращает подпись статуса комьюнити.
     */
    public function statusLabel(?int $status): string
    {
        return CommunityStatus::labelFor($status);
    }

    /**
     * Обновляет комьюнити из DTO.
     */
    public function updateFromData(CommunityData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
