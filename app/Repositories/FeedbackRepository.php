<?php

namespace App\Repositories;

use App\DTO\Feedback\FeedbackData;
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
        return $this->create($data->toArray() + ['time' => now()]);
    }
}
