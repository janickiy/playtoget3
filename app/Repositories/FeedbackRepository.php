<?php

namespace App\Repositories;

use App\DTO\Admin\Feedback\FeedbackAdminData;
use App\DTO\Feedback\FeedbackData;
use App\Enums\FeedbackStatus;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeedbackRepository extends BaseRepository
{
    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(Feedback $model)
    {
        parent::__construct($model);
    }

    /**
     * Создает запись из DTO с подготовленными данными.
     */
    public function createFromData(FeedbackData $data): Builder|Model
    {
        return $this->create($data->toArray() + [
            'status' => FeedbackStatus::New->value,
            'time' => now(),
        ]);
    }

    /**
     * Возвращает варианты статусов обращений для формы админки.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return FeedbackStatus::options();
    }

    /**
     * Обновляет статус и ответ обращения из DTO.
     */
    public function updateFromAdminData(FeedbackAdminData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
