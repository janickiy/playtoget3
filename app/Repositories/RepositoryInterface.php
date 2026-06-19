<?php

namespace App\Repositories;

use App\DTO\DataTransferObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Returns all model records; needed for simple reading scenarios without filters.
     */
    public function all(): Collection;

    /**
     * Looks for record by primary key; used as basic secure access to the model.
     */
    public function find(int|string $id): ?Model;

    /**
     * Creates record from an array of attributes; A common low-level method for heirs.
     */
    public function create(array $data): Builder|Model;

    /**
     * Creates record from DTO; It is necessary for record data to go through a typed contract.
     */
    public function createFromDto(DataTransferObject $dto): Builder|Model;

    /**
     * Updates record by primary key by array of attributes; returns the result of saving.
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Updates record by primary key data from DTO; used for typed update operations.
     */
    public function updateFromDto(int|string $id, DataTransferObject $dto): bool;

    /**
     * Delete record by primary key; returns false if record is not found.
     */
    public function delete(int|string $id): bool;

    /**
     * Delete all model records without resetting sequence; needed to clean the table using query builder.
     */
    public function deleteAll(): void;

    /**
     * Completely clears the model table with counters reset where the driver supports it.
     */
    public function truncate(): void;
}
