<?php

namespace App\Repositories;

use App\Models\Content;

class ContentRepository extends BaseRepository
{
    public function __construct(Content $model)
    {
        parent::__construct($model);
    }

    public function visible(int $id): ?Content
    {
        /** @var Content|null $page */
        $page = $this->model->newQuery()
            ->whereKey($id)
            ->where('hide', 'show')
            ->first();

        return $page;
    }
}
