<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Collection;

class EventRepository extends BaseRepository
{
    public function __construct(Event $model)
    {
        parent::__construct($model);
    }

    public function upcoming(int $limit = 30): Collection
    {
        return $this->model->newQuery()
            ->where('banned', false)
            ->orderByRaw('date_from IS NULL')
            ->orderBy('date_from')
            ->limit($limit)
            ->get();
    }
}
