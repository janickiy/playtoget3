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
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(Feedback $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates record from DTO with prepared data.
     */
    public function createFromData(FeedbackData $data): Builder|Model
    {
        return $this->create($data->toArray() + [
            'status' => FeedbackStatus::New->value,
            'time' => now(),
        ]);
    }

    /**
     * Returns feedback request status options for the admin panel form.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return FeedbackStatus::options();
    }

    /**
     * Updates status and response from the DTO.
     */
    public function updateFromAdminData(FeedbackAdminData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
