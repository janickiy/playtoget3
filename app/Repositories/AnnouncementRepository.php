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
     * Connects the announcement model that the repository works with.
     */
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates an announcement from a DTO with a unique slug.
     *
     * @param AnnouncementData $data
     * @return Builder|Model
     */
    public function createFromData(AnnouncementData $data): Builder|Model
    {
        return $this->create($this->payload($data));
    }

    /**
     * Updates announcement from DTO with a unique slug.
     */
    public function updateFromData(AnnouncementData $data): bool
    {
        return $this->update($data->id, $this->payload($data, $data->id));
    }

    /**
     * Returns a published announcement by slug.
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
     * Returns latest published announcement.
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
     * Returns all published announcements for the page section.
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
     * Prepares an array of announcement fields before saving.
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
     * Builds a unique slug announcement.
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
     * Checks, whether the slug is used by another announcement.
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
