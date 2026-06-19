<?php

namespace App\Repositories;

use App\DTO\SportLevel\SportLevelData;
use App\Models\SportLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SportLevelRepository extends BaseRepository
{
    /**
     * Connects the sport level model with which the repository works.
     */
    public function __construct(SportLevel $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns query builder for the sport level table.
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Creates a sport level from DTO.
     */
    public function createFromData(SportLevelData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Updates a sport level from DTO.
     */
    public function updateFromData(SportLevelData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Returns all sport levels ordered by name.
     *
     * @return Collection<int, SportLevel>
     */
    public function ordered(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('name')
            ->get();
    }
}
