<?php

namespace App\Service;

use App\DTO\SportLevel\SportLevelData;
use App\Models\SportLevel;
use App\Repositories\SportLevelRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SportLevelService
{
    /**
     * Connects repository для управления sport levels.
     */
    public function __construct(
        private readonly SportLevelRepository $sportLevelRepository,
    ) {
    }

    /**
     * Creates новый sport level.
     */
    public function create(SportLevelData $data): Builder|Model
    {
        return $this->sportLevelRepository->createFromData($data);
    }

    /**
     * Updates выбранный sport level.
     */
    public function update(SportLevelData $data): bool
    {
        return $this->sportLevelRepository->updateFromData($data);
    }

    /**
     * Deletes выбранный sport level.
     */
    public function delete(int $id): bool
    {
        return $this->sportLevelRepository->delete($id);
    }

    /**
     * Returns sport level для страницы просмотра или редактирования.
     */
    public function find(int $id): ?SportLevel
    {
        /** @var SportLevel|null $sportLevel */
        $sportLevel = $this->sportLevelRepository->find($id);

        return $sportLevel;
    }
}
