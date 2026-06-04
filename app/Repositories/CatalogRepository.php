<?php

namespace App\Repositories;

use App\DTO\Catalog\CatalogData;
use App\Models\Catalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatalogRepository extends BaseRepository
{
    public function __construct(Catalog $model)
    {
        parent::__construct($model);
    }

    public function createFromData(CatalogData $data): Builder|Model
    {
        return $this->create($data->toArray());
    }

    public function updateFromData(CatalogData $data): bool
    {
        return $this->update($data->id(), $data->toArray());
    }
}
