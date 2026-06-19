<?php

namespace App\Repositories;

use App\DTO\SportType\SportTypeData;
use App\Models\SportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SportTypeRepository extends BaseRepository
{
    /**
     * Connects the sport type model with which the repository works.
     */
    public function __construct(SportType $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns query builder for the sport types table.
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Creates sport type из DTO.
     */
    public function createFromData(SportTypeData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Updates sport type из DTO.
     */
    public function updateFromData(SportTypeData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Returns sport type together with parent relation.
     */
    public function findWithParent(int $id): ?SportType
    {
        /** @var SportType|null $sportType */
        $sportType = $this->model->newQuery()
            ->with('parent')
            ->find($id);

        return $sportType;
    }

    /**
     * Returns options для select parent sport type.
     *
     * @return array<int, string>
     */
    public function parentOptions(?int $excludeId = null): array
    {
        return $this->model->newQuery()
            ->when($excludeId !== null, fn(Builder $query): Builder => $query->whereKeyNot($excludeId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Returns все sport types ordered by name.
     *
     * @return Collection<int, SportType>
     */
    public function ordered(): Collection
    {
        return $this->model->newQuery()
            ->with('parent')
            ->orderBy('name')
            ->get();
    }
}
