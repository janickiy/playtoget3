<?php

namespace App\Repositories;

use App\Models\NewsRssSport;
use Illuminate\Support\Collection;

class NewsRepository extends BaseRepository
{
    public function __construct(NewsRssSport $model)
    {
        parent::__construct($model);
    }

    public function latest(int $limit = 20): Collection
    {
        return $this->model->newQuery()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
