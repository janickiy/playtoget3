<?php

namespace App\Repositories;

use App\DTO\Content\ContentData;
use App\Helpers\StringHelper;
use App\Models\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
     * Создает страницу или раздел из DTO с подготовленным уникальным slug.
     */
    public function createFromData(ContentData $data): Builder|Model
    {
        return $this->create($this->payload($data));
    }

    /**
     * Обновляет страницу или раздел из DTO с подготовленным уникальным slug.
     */
    public function updateFromData(ContentData $data): bool
    {
        return $this->update($data->id, $this->payload($data, $data->id));
    }

    /**
     * Возвращает опубликованную контентную страницу по slug.
     */
    public function visibleBySlug(string $slug): ?Content
    {
        /** @var Content|null $page */
        $page = $this->model->newQuery()
            ->where('slug', $slug)
            ->where('published', true)
            ->first();

        return $page;
    }

    /**
     * Готовит массив полей для сохранения страницы.
     *
     * @return array<string, mixed>
     */
    private function payload(ContentData $data, ?int $ignoreId = null): array
    {
        $payload = $data->toArray();
        $payload['slug'] = $this->uniqueSlug($data->slug ?: $data->title, $ignoreId);

        return $payload;
    }

    /**
     * Формирует уникальный slug для страницы, игнорируя текущую запись при редактировании.
     */
    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = trim(StringHelper::slug($source), '-');
        $base = $base !== '' ? $base : 'page';
        $slug = $base;
        $index = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $index;
            $index++;
        }

        return $slug;
    }

    /**
     * Проверяет, занят ли slug другой страницей.
     */
    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
