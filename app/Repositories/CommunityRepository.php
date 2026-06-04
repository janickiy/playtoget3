<?php

namespace App\Repositories;

use App\Models\Community;
use Illuminate\Support\Collection;

class CommunityRepository extends BaseRepository
{
    public function __construct(Community $model)
    {
        parent::__construct($model);
    }

    public function teams(): Collection
    {
        return $this->model->newQuery()
            ->where('type', 'team')
            ->where('banned', false)
            ->orderBy('name')
            ->get();
    }

    public function findTeam(int $id): ?Community
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()
            ->where('type', 'team')
            ->whereKey($id)
            ->first();

        return $community;
    }
}
