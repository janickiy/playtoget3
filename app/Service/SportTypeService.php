<?php

namespace App\Service;

use App\DTO\SportType\SportTypeData;
use App\Models\SportType;
use App\Repositories\SportTypeRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SportTypeService
{
    /**
     * Connects repository for managing sport types.
     */
    public function __construct(
        private readonly SportTypeRepository $sportTypeRepository,
    ) {
    }

    /**
     * Creates a new sport type.
     */
    public function create(SportTypeData $data): Builder|Model
    {
        return $this->sportTypeRepository->createFromData($data);
    }

    /**
     * Updates selected sport type.
     */
    public function update(SportTypeData $data): bool
    {
        return $this->sportTypeRepository->updateFromData($data);
    }

    /**
     * Delete the selected sport type.
     */
    public function delete(int $id): bool
    {
        return $this->sportTypeRepository->delete($id);
    }

    /**
     * Returns sport type for view or edit page.
     */
    public function find(int $id): ?SportType
    {
        return $this->sportTypeRepository->findWithParent($id);
    }

    /**
     * Returns options to select parent sport type.
     *
     * @return array<int, string>
     */
    public function parentOptions(?int $excludeId = null): array
    {
        return $this->sportTypeRepository->parentOptions($excludeId);
    }
}
