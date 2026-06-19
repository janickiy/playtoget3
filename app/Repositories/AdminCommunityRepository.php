<?php

namespace App\Repositories;

use App\DTO\Admin\CommunityData;
use App\Enums\CommunityStatus;
use App\Models\Community;
use App\Service\ContentCascadeDeleteService;

class AdminCommunityRepository extends BaseRepository
{
    /**
     * Connects community model with which the admin repository works.
     */
    public function __construct(
        Community $model,
        private readonly ContentCascadeDeleteService $cascadeDelete,
    ) {
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
     * Updates community from DTO.
     */
    public function updateFromData(CommunityData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Deletes community together with all related content and media.
     */
    public function delete(int|string $id): bool
    {
        /** @var Community|null $community */
        $community = $this->model->newQuery()->find($id);

        return $community ? $this->cascadeDelete->deleteCommunity($community) : false;
    }
}
