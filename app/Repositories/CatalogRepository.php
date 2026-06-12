<?php

namespace App\Repositories;

use App\DTO\Catalog\CatalogData;
use App\Models\Catalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatalogRepository extends BaseRepository
{
    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(Catalog $model)
    {
        parent::__construct($model);
    }

    /**
     * Создает запись из DTO с подготовленными данными.
     */
    public function createFromData(CatalogData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    /**
     * Обновляет запись из DTO с подготовленными данными.
     */
    public function updateFromData(CatalogData $data): bool
    {
        return $this->update($data->id(), $data->toArray());
    }
}
