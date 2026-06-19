<?php

namespace App\Repositories;

use App\DTO\SportBlock\SportBlockData;
use App\Enums\SportBlockStatus;
use App\Enums\UserStatus;
use App\Helpers\FrontAssets;
use App\Models\GeoCity;
use App\Models\SportBlock;
use App\Models\User;
use App\Repositories\Concerns\SyncsGeoTargets;
use App\Service\SportBlockAvatarService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SportBlockRepository extends BaseRepository
{
    use SyncsGeoTargets;

    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(SportBlock $model, private readonly SportBlockAvatarService $avatars)
    {
        parent::__construct($model);
    }

    /**
     * Returns sport objects of the specified type with filters and pagination.
     *
     * @param string $type
     * @param array $filters
     * @param int|null $limit
     * @param int $offset
     * @return Collection
     */
    public function byType(string $type, array $filters = [], ?int $limit = null, int $offset = 0): Collection
    {
        $query = $this->queryByType($type, $filters)->orderBy('name');

        if ($limit !== null) {
            $query->offset(max($offset, 0))->limit($limit);
        }

        return $query->get();
    }

    /**
     * Returns sport objects of the specified type already prepared for docs.
     *
     * @param string $type
     * @param array $filters
     * @param int|null $limit
     * @param int $offset
     * @return Collection
     */
    public function serializedByType(string $type, array $filters = [], ?int $limit = null, int $offset = 0): Collection
    {
        return $this->byType($type, $filters, $limit, $offset)
            ->map(fn (SportBlock $sportBlock): array => $this->serialize($sportBlock));
    }

    /**
     * Counts sport objects of the specified type, taking into account filters.
     *
     * @param string $type
     * @param array $filters
     * @return int
     */
    public function countByType(string $type, array $filters = []): int
    {
        return (int) $this->queryByType($type, $filters)->count();
    }

    /**
     * Finds sport object by id and type.
     *
     * @param int $id
     * @param string $type
     * @return SportBlock|null
     */
    public function findByType(int $id, string $type): ?SportBlock
    {
        /** @var SportBlock|null $sportBlock */
        $sportBlock = $this->model->newQuery()
            ->where('type', $type)
            ->whereIn('status', SportBlockStatus::visibleValues())
            ->whereDoesntHave('owner', function (Builder $query): void {
                $query->whereIn('status', [
                    UserStatus::Blocked->value,
                    UserStatus::Deleted->value,
                ]);
            })
            ->whereKey($id)
            ->first();

        return $sportBlock;
    }

    /**
     * Converts the model to a data array for docs.
     *
     * @param SportBlock $sportBlock
     * @return array
     */
    public function serialize(SportBlock $sportBlock): array
    {
        return [
            'id' => (int) $sportBlock->id,
            'name' => (string) $sportBlock->name,
            'about' => (string) $sportBlock->about,
            'place' => (string) $sportBlock->place,
            'address' => (string) $sportBlock->address,
            'phone' => (string) $sportBlock->phone,
            'email' => (string) $sportBlock->email,
            'website' => (string) $sportBlock->website,
            'avatar' => FrontAssets::sportBlockAvatar($sportBlock),
            'owner_id' => (int) $sportBlock->owner_id,
            'type' => (string) $sportBlock->type,
        ];
    }

    /**
     * Creates sport object on behalf of the owner.
     *
     * @param User $owner
     * @param string $type
     * @param SportBlockData $data
     * @return SportBlock
     * @throws \Throwable
     */
    public function createBlock(User $owner, string $type, SportBlockData $data): SportBlock
    {
        return DB::transaction(function () use ($owner, $type, $data): SportBlock {
            $avatar = $this->avatars->storeAvatar($data->avatarFile);

            /** @var SportBlock $sportBlock */
            $sportBlock = $this->model->newQuery()->create([
                'type' => $type,
                'owner_id' => $owner->id,
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'address' => $data->address,
                'phone' => $data->phone,
                'email' => $data->email,
                'website' => $data->website,
                'avatar' => $avatar ?? '',
                'status' => SportBlockStatus::Confirmed->value,
            ]);

            $this->syncGeoTarget($sportBlock->type, (int) $sportBlock->id, $data->cityId);

            return $sportBlock;
        });
    }

    /**
     * Updates sport object and related data.
     *
     * @param SportBlock $sportBlock
     * @param SportBlockData $data
     * @return bool
     * @throws \Throwable
     */
    public function updateBlock(SportBlock $sportBlock, SportBlockData $data): bool
    {
        return DB::transaction(function () use ($sportBlock, $data): bool {
            $avatar = $this->avatars->storeAvatar($data->avatarFile);

            $sportBlock->fill([
                'name' => $data->name,
                'about' => $data->about,
                'place' => $data->place ?: $this->cityName($data->cityId),
                'address' => $data->address,
                'phone' => $data->phone,
                'email' => $data->email,
                'website' => $data->website,
            ]);

            if ($avatar) {
                $sportBlock->avatar = $avatar;
            }

            $sportBlock->save();
            $this->syncGeoTarget($sportBlock->type, (int) $sportBlock->id, $data->cityId);

            return true;
        });
    }

    /**
     * Returns name city by its identifier.
     */
    public function cityName(?int $cityId): string
    {
        if (! $cityId) {
            return '';
        }

        return (string) (GeoCity::query()->find($cityId)?->name_ru ?? '');
    }

    /**
     * Checks whether the user is the owner of the entity.
     */
    public function isOwner(?SportBlock $sportBlock, ?User $viewer): bool
    {
        return $sportBlock && $viewer && (int) $sportBlock->owner_id === (int) $viewer->id;
    }

    /**
     * Prepares a request for sports objects by type and filters.
     *
     * @param string $type
     * @param array $filters
     * @return Builder
     */
    private function queryByType(string $type, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $place = trim((string) ($filters['place'] ?? ''));

        if ($place === '' && (int) ($filters['id_place'] ?? 0) > 0) {
            $place = $this->cityName((int) $filters['id_place']);
        }

        return $this->model->newQuery()
            ->where('type', $type)
            ->whereIn('status', SportBlockStatus::visibleValues())
            ->whereDoesntHave('owner', function (Builder $query): void {
                $query->whereIn('status', [
                    UserStatus::Blocked->value,
                    UserStatus::Deleted->value,
                ]);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('about', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%');
                });
            })
            ->when($place !== '', fn (Builder $query) => $query->where('place', 'like', '%' . $place . '%'));
    }

}
