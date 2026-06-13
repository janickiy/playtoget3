<?php

namespace App\Repositories;

use App\DTO\Settings\SettingsData;
use App\Models\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SettingsRepository extends BaseRepository
{
    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(Settings $model)
    {
        parent::__construct($model);
    }

    /**
     * Создает запись из DTO с подготовленными данными.
     */
    public function createFromData(SettingsData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Обновляет запись из DTO с подготовленными данными.
     *
     * @param SettingsData $data
     * @return bool
     */
    public function updateFromData(SettingsData $data): bool
    {
        return $this->update($data->id, $data->toArray());
    }

    /**
     * Удаляет запись настроек и связанный файл при необходимости.
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
