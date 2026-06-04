<?php

namespace App\Repositories;

use App\Models\SportBlock;
use Illuminate\Support\Collection;

class SportBlockRepository extends BaseRepository
{
    public function __construct(SportBlock $model)
    {
        parent::__construct($model);
    }

    public function byType(string $type): Collection
    {
        return $this->model->newQuery()
            ->where('type', $type)
            ->where('banned', false)
            ->orderBy('name')
            ->get();
    }

    public function findByType(int $id, string $type): ?SportBlock
    {
        /** @var SportBlock|null $sportBlock */
        $sportBlock = $this->model->newQuery()
            ->where('type', $type)
            ->whereKey($id)
            ->first();

        return $sportBlock;
    }
}
