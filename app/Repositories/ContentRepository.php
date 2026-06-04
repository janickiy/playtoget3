<?php

namespace App\Repositories;

use App\Models\ContentPage;

class ContentRepository extends BaseRepository
{
    public function __construct(ContentPage $model)
    {
        parent::__construct($model);
    }

    public function visible(int $id): ?ContentPage
    {
        /** @var ContentPage|null $page */
        $page = $this->model->newQuery()
            ->whereKey($id)
            ->where('hide', 'show')
            ->first();

        return $page;
    }
}
