<?php

namespace App\Repositories;

use App\DTO\Admin\SportBlockData;
use App\Enums\SportBlockStatus;
use App\Models\SportBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminSportBlockRepository extends BaseRepository
{
    /**
     * Connects модель sport block, с которой работает админский репозиторий.
     */
    public function __construct(SportBlock $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns options typeов sport blocks для form admin panel.
     *
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return [
            'playground' => 'Playground',
            'shop' => 'Shop',
            'fitness' => 'Fitness',
        ];
    }

    /**
     * Returns signature typeа sport block.
     */
    public function typeLabel(?string $type): string
    {
        return $this->typeOptions()[$type] ?? (string) $type;
    }

    /**
     * Returns options statusов sport blocks для form admin panel.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return SportBlockStatus::options();
    }

    /**
     * Returns signature statusа sport block.
     */
    public function statusLabel(?int $status): string
    {
        return SportBlockStatus::labelFor($status);
    }

    /**
     * Creates sport block из DTO.
     *
     * @param SportBlockData $data
     * @return Builder|Model
     */
    public function createFromData(SportBlockData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Updates sport block из DTO.
     */
    public function updateFromData(SportBlockData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
