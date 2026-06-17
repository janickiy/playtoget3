<?php

namespace App\Repositories;

use App\DTO\Admin\CommunityData;
use App\Enums\CommunityStatus;
use App\Models\Community;

class AdminCommunityRepository extends BaseRepository
{
    /**
     * Connects модель community, с которой работает админский репозиторий.
     */
    public function __construct(Community $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns options typeов community для form admin panel.
     *
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return [
            'team' => 'Team',
            'group' => 'Group',
        ];
    }

    /**
     * Returns signature typeа community.
     */
    public function typeLabel(?string $type): string
    {
        return $this->typeOptions()[$type] ?? (string) $type;
    }

    /**
     * Returns options statusов community для form admin panel.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return CommunityStatus::options();
    }

    /**
     * Returns signature statusа community.
     */
    public function statusLabel(?int $status): string
    {
        return CommunityStatus::labelFor($status);
    }

    /**
     * Updates community из DTO.
     */
    public function updateFromData(CommunityData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }
}
