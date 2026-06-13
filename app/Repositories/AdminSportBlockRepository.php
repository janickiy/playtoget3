<?php

namespace App\Repositories;

use App\DTO\Admin\SportBlockData;
use App\Enums\SportBlockStatus;
use App\Models\SportBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminSportBlockRepository extends BaseRepository
{
    /**
     * Подключает модель спортивного блока, с которой работает админский репозиторий.
     */
    public function __construct(SportBlock $model)
    {
        parent::__construct($model);
    }

    /**
     * Возвращает варианты типов спортивных блоков для формы админки.
     *
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return [
            'playground' => 'Площадка',
            'shop' => 'Магазин',
            'fitness' => 'Фитнес',
        ];
    }

    /**
     * Возвращает подпись типа спортивного блока.
     */
    public function typeLabel(?string $type): string
    {
        return $this->typeOptions()[$type] ?? (string) $type;
    }

    /**
     * Возвращает варианты статусов спортивных блоков для формы админки.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return SportBlockStatus::options();
    }

    /**
     * Возвращает подпись статуса спортивного блока.
     */
    public function statusLabel(?int $status): string
    {
        return SportBlockStatus::labelFor($status);
    }

    /**
     * Создает спортивный блок из DTO.
     *
     * @param SportBlockData $data
     * @return Builder|Model
     */
    public function createFromData(SportBlockData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Обновляет спортивный блок из DTO.
     */
    public function updateFromData(SportBlockData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
