<?php

namespace App\Repositories;

use App\DTO\Announcement\AnnouncementData;
use App\Helpers\StringHelper;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AnnouncementRepository extends BaseRepository
{
    /**
     * Подключает модель объявления, с которой работает репозиторий.
     */
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }

    /**
     * Создает объявление из DTO с уникальным slug.
     *
     * @param AnnouncementData $data
     * @return Builder|Model
     */
    public function createFromData(AnnouncementData $data): Builder|Model
    {
        return $this->create($this->payload($data));
    }

    /**
     * Обновляет объявление из DTO с уникальным slug.
     */
    public function updateFromData(AnnouncementData $data): bool
    {
        return $this->update($data->id, $this->payload($data, $data->id));
    }

    /**
     * Возвращает опубликованное объявление по slug.
     */
    public function visibleBySlug(string $slug): ?Announcement
    {
        /** @var Announcement|null $announcement */
        $announcement = $this->model->newQuery()
            ->where('slug', $slug)
            ->where('published', true)
            ->first();

        return $announcement;
    }

    /**
     * Возвращает последние опубликованные объявления.
     *
     * @param int $limit
     * @return Collection<int, Announcement>
     */
    public function latestVisible(int $limit = 3): Collection
    {
        return $this->model->newQuery()
            ->where('published', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Возвращает все опубликованные объявления для страницы раздела.
     *
     * @return Collection<int, Announcement>
     */
    public function visibleList(): Collection
    {
        return $this->model->newQuery()
            ->where('published', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Готовит массив полей объявления перед сохранением.
     *
     * @param AnnouncementData $data
     * @param int|null $ignoreId
     * @return array<string, mixed>
     */
    private function payload(AnnouncementData $data, ?int $ignoreId = null): array
    {
        $payload = $data->toArray();
        $payload['slug'] = $this->uniqueSlug($data->slug ?: $data->title, $ignoreId);

        return $payload;
    }

    /**
     * Формирует уникальный slug объявления.
     */
    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = trim(StringHelper::slug($source), '-');
        $base = $base !== '' ? $base : 'announcement';
        $slug = $base;
        $index = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $index;
            $index++;
        }

        return $slug;
    }

    /**
     * Проверяет, занят ли slug другим объявлением.
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
