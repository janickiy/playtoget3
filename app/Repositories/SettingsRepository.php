<?php

namespace App\Repositories;

use App\DTO\Settings\SettingsData;
use App\Models\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SettingsRepository extends BaseRepository
{
    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(Settings $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates record from DTO with prepared data.
     */
    public function createFromData(SettingsData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Updates record from DTO with prepared data.
     *
     * @param SettingsData $data
     * @return bool
     */
    public function updateFromData(SettingsData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Deletes record settings and associated file if necessary.
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {
        /** @var Settings|null $settings */
        $settings = $this->model->newQuery()->find($id);

        if ($settings === null) {
            return false;
        }

        if ((string) $settings->type === 'FILE') {
            Settings::deleteFile($settings->filePath(), Settings::getTableName());
        }

        return (bool) $settings->delete();
    }
}
