<?php

namespace App\Repositories;

use App\DTO\SportBlock\SportBlockData;
use App\Helpers\FrontAssets;
use App\Models\GeoCity;
use App\Models\GeoTarget;
use App\Models\SportBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SportBlockRepository extends BaseRepository
{
    public function __construct(SportBlock $model)
    {
        parent::__construct($model);
    }

    public function byType(string $type, array $filters = [], ?int $limit = null, int $offset = 0): Collection
    {
        $query = $this->queryByType($type, $filters)->orderBy('name');

        if ($limit !== null) {
            $query->offset(max($offset, 0))->limit($limit);
        }

        return $query->get();
    }

    public function serializedByType(string $type, array $filters = [], ?int $limit = null, int $offset = 0): Collection
    {
        return $this->byType($type, $filters, $limit, $offset)
            ->map(fn (SportBlock $sportBlock): array => $this->serialize($sportBlock));
    }

    public function countByType(string $type, array $filters = []): int
    {
        return (int) $this->queryByType($type, $filters)->count();
    }

    public function findByType(int $id, string $type): ?SportBlock
    {
        /** @var SportBlock|null $sportBlock */
        $sportBlock = $this->model->newQuery()
            ->where('type', $type)
            ->where('banned', false)
            ->whereKey($id)
            ->first();

        return $sportBlock;
    }

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
            'active' => (bool) $sportBlock->active,
            'type' => (string) $sportBlock->type,
        ];
    }

    public function createBlock(User $owner, string $type, SportBlockData $data): SportBlock
    {
        return DB::transaction(function () use ($owner, $type, $data): SportBlock {
            $avatar = $this->storeAvatar($data->avatarFile);

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
                'active' => true,
                'banned' => false,
            ]);

            $this->syncGeoTarget($sportBlock, $data->cityId);

            return $sportBlock;
        });
    }

    public function updateBlock(SportBlock $sportBlock, SportBlockData $data): bool
    {
        return DB::transaction(function () use ($sportBlock, $data): bool {
            $avatar = $this->storeAvatar($data->avatarFile);

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
            $this->syncGeoTarget($sportBlock, $data->cityId);

            return true;
        });
    }

    public function cityName(?int $cityId): string
    {
        if (! $cityId) {
            return '';
        }

        return (string) (GeoCity::query()->find($cityId)?->name_ru ?? '');
    }

    public function isOwner(?SportBlock $sportBlock, ?User $viewer): bool
    {
        return $sportBlock && $viewer && (int) $sportBlock->owner_id === (int) $viewer->id;
    }

    private function queryByType(string $type, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $place = trim((string) ($filters['place'] ?? ''));

        if ($place === '' && (int) ($filters['id_place'] ?? 0) > 0) {
            $place = $this->cityName((int) $filters['id_place']);
        }

        return $this->model->newQuery()
            ->where('type', $type)
            ->where('banned', false)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('about', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%');
                });
            })
            ->when($place !== '', fn (Builder $query) => $query->where('place', 'like', '%' . $place . '%'));
    }

    private function syncGeoTarget(SportBlock $sportBlock, int $cityId): void
    {
        if ($cityId < 1) {
            return;
        }

        GeoTarget::query()->updateOrCreate([
            'target_type' => $sportBlock->type,
            'target_id' => $sportBlock->id,
        ], [
            'city_id' => $cityId,
        ]);
    }

    private function storeAvatar(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = Str::lower(md5(microtime(true) . $file->getClientOriginalName() . Str::random(8))) . '.' . $extension;

        return $file->storeAs('images/sportblocks/avatar', $filename, 'public') ? $filename : null;
    }
}
