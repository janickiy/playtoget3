<?php

namespace App\Repositories;


use App\DTO\DataTransferObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{

    /**
     * Saves the Eloquent model that a specific repository works with.
     *
     * @param Model $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * Creates a new record from the attributes array; basic operation for all heirs.
     *
     * @param array $data
     * @return Builder|Model
     */
    public function create(array $data): Builder|Model
    {
        return $this->model->create($data);
    }

    /**
     * Creates a new record from DTO; needed to prevent services from passing raw arrays directly.
     */
    public function createFromDto(DataTransferObject $dto): Builder|Model
    {
        return $this->create($dto->toArray());
    }

    /**
     * Updates record by id provided attributes; returns false if record is not found.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): bool
    {
        $model = $this->model->find($id);

        if ($model) {
            return $model->fill($data)->save();
        }

        return false;
    }

    /**
     * Updates record by id data from DTO; needed for a single typed update-approach.
     */
    public function updateFromDto(int|string $id, DataTransferObject $dto): bool
    {
        return $this->update($id, $dto->toArray());
    }

    /**
     * Returns all records of the model table; used only for simple samples without filters.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Looks for record by primary key and returns null if there is no record.
     *
     * @return Model|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Delete record by primary key; needed for the general deletion operation in descendants.
     */
    public function delete(int|string $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->delete();
            return true;
        }
        return false;
    }

    /**
     * Delete all records via query builder without recreating the table.
     */
    public function deleteAll(): void
    {
        $this->model->query()->delete();
    }

    /**
     * Completely clears the model table; useful for utility cleanup and testing.
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }

}
