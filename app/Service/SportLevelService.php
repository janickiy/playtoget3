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
     * Connects repository for managing sport levels.
     */
    public function __construct(
        private readonly SportLevelRepository $sportLevelRepository,
    ) {
    }

    /**
     * Creates a new sport level.
     */
    public function create(SportLevelData $data): Builder|Model
    {
        return $this->sportLevelRepository->createFromData($data);
    }

    /**
     * Updates selected sport level.
     */
    public function update(SportLevelData $data): bool
    {
        return $this->sportLevelRepository->updateFromData($data);
    }

    /**
     * Delete the selected sport level.
     */
    public function delete(int $id): bool
    {
        return $this->sportLevelRepository->delete($id);
    }

    /**
     * Returns sport level for view or edit page.
     */
    public function find(int $id): ?SportLevel
    {
        /** @var SportLevel|null $sportLevel */
        $sportLevel = $this->sportLevelRepository->find($id);

        return $sportLevel;
    }
}
