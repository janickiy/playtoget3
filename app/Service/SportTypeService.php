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
     * Connects repository для управления sport types.
     */
    public function __construct(
        private readonly SportTypeRepository $sportTypeRepository,
    ) {
    }

    /**
     * Creates новый sport type.
     */
    public function create(SportTypeData $data): Builder|Model
    {
        return $this->sportTypeRepository->createFromData($data);
    }

    /**
     * Updates выбранный sport type.
     */
    public function update(SportTypeData $data): bool
    {
        return $this->sportTypeRepository->updateFromData($data);
    }

    /**
     * Deletes выбранный sport type.
     */
    public function delete(int $id): bool
    {
        return $this->sportTypeRepository->delete($id);
    }

    /**
     * Returns sport type для страницы просмотра или редактирования.
     */
    public function find(int $id): ?SportType
    {
        return $this->sportTypeRepository->findWithParent($id);
    }

    /**
     * Returns options для выбора parent sport type.
     *
     * @return array<int, string>
     */
    public function parentOptions(?int $excludeId = null): array
    {
        return $this->sportTypeRepository->parentOptions($excludeId);
    }
}
