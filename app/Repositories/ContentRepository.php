<?php

namespace App\Repositories;

use App\Models\Content;

class ContentRepository extends BaseRepository
{
    /**
     * Подключает модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(Content $model)
    {
        parent::__construct($model);
    }

    /**
     * Возвращает опубликованную контентную страницу по id.
     */
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
