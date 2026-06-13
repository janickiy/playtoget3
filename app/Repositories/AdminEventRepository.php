<?php

namespace App\Repositories;

use App\DTO\Admin\EventData;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminEventRepository extends BaseRepository
{
    /**
     * Подключает модель мероприятия, с которой работает админский репозиторий.
     */
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    /**
     * Возвращает варианты статусов мероприятий для формы админки.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return EventStatus::options();
    }

    /**
     * Возвращает подпись статуса мероприятия.
     */
    public function statusLabel(?int $status): string
    {
        return EventStatus::labelFor($status);
    }

    /**
     * Создает мероприятие из DTO.
     *
     * @param EventData $data
     * @return Builder|Model
     */
    public function createFromData(EventData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Обновляет мероприятие из DTO.
     */
    public function updateFromData(EventData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
