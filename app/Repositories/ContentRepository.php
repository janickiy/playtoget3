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
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(Content $model)
    {
        parent::__construct($model);
    }

    /**
     * Creates a page or section from a DTO with a unique slug prepared.
     *
     * @param ContentData $data
     * @return Builder|Model
     */
    public function createFromData(ContentData $data): Builder|Model
    {
        return $this->create($this->payload($data));
    }

    /**
     * Updates page or section from a DTO with a unique slug prepared.
     *
     * @param ContentData $data
     * @return bool
     */
    public function updateFromData(ContentData $data): bool
    {
        return $this->update($data->id, $this->payload($data, $data->id));
    }

    /**
     * Returns published content page by slug.
     *
     * @param string $slug
     * @return Content|null
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
     * Prepares an array of fields for saving page.
     *
     * @param ContentData $data
     * @param int|null $ignoreId
     * @return array
     */
    private function payload(ContentData $data, ?int $ignoreId = null): array
    {
        $payload = $data->toArray();
        $payload['slug'] = $this->uniqueSlug($data->slug ?: $data->title, $ignoreId);

        return $payload;
    }


    /**
     * Builds a unique slug for page, ignoring the current record when responding
     *
     * @param string $source
     * @param int|null $ignoreId
     * @return string
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
     * Checks whether the slug is occupied by another page.
     *
     * @param string $slug
     * @param int|null $ignoreId
     * @return bool
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
