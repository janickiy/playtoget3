<?php

namespace App\Repositories;

use App\DTO\Admin\EventData;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Service\ContentCascadeDeleteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminEventRepository extends BaseRepository
{
    /**
     * Connects event model, with which the admin repository works.
     */
    public function __construct(
        Event $model,
        private readonly ContentCascadeDeleteService $cascadeDelete,
    ) {
        parent::__construct($model);
    }

    /**
     * Returns event status options for the admin panel form.
     *
     * @return array<int, string>
     */
    public function statusOptions(): array
    {
        return EventStatus::options();
    }

    /**
     * Returns the event status label.
     */
    public function statusLabel(?int $status): string
    {
        return EventStatus::labelFor($status);
    }

    /**
     * Creates an event from DTO.
     *
     * @param EventData $data
     * @return Builder|Model
     */
    public function createFromData(EventData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Updates an event from DTO.
     */
    public function updateFromData(EventData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Deletes event together with albums, members, reactions and media files.
     */
    public function delete(int|string $id): bool
    {
        /** @var Event|null $event */
        $event = $this->model->newQuery()->find($id);

        return $event ? $this->cascadeDelete->deleteEvent($event) : false;
    }
}
