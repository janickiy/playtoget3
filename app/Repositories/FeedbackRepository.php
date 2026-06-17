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
     * Connects модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(Feedback $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates record из DTO с подготовленными data.
     */
    public function createFromData(FeedbackData $data): Builder|Model
    {
        return $this->create($data->toArray() + [
            'status' => FeedbackStatus::New->value,
            'time' => now(),
        ]);
    }

    /**
     * Returns options statusов feedback requests для form admin panel.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return FeedbackStatus::options();
    }

    /**
     * Updates status и ответ обращения из DTO.
     */
    public function updateFromAdminData(FeedbackAdminData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
