<?php

namespace App\Repositories;

use App\Helpers\FrontAssets;
use App\Models\GeoCity;
use App\Models\GeoTarget;
use App\Models\SportBlock;
use App\Models\User;
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

    public function byType(string $type, array $filters = []): Collection
    {
        return $this->model->newQuery()
            ->where('type', $type)
            ->where('banned', false)
            ->when(($filters['search'] ?? '') !== '', function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('about', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%');
                });
            })
            ->when(($filters['place'] ?? '') !== '', fn ($query) => $query->where('place', 'like', '%' . trim((string) $filters['place']) . '%'))
            ->orderBy('name')
            ->get();
    }

    public function serializedByType(string $type, array $filters = []): Collection
    {
        return $this->byType($type, $filters)
            ->map(fn (SportBlock $sportBlock): array => $this->serialize($sportBlock));
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

    public function createBlock(User $owner, string $type, array $data): SportBlock
    {
        return DB::transaction(function () use ($owner, $type, $data): SportBlock {
            $avatar = $this->storeAvatar($data['avatar_file'] ?? null);

            /** @var SportBlock $sportBlock */
            $sportBlock = $this->model->newQuery()->create([
                'type' => $type,
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? '',
                'website' => $data['website'] ?? '',
                'avatar' => $avatar ?? '',
                'active' => true,
                'banned' => false,
            ]);

            $this->syncGeoTarget($sportBlock, (int) ($data['city_id'] ?? 0));

            return $sportBlock;
        });
    }

    public function updateBlock(SportBlock $sportBlock, array $data): bool
    {
        return DB::transaction(function () use ($sportBlock, $data): bool {
            $avatar = $this->storeAvatar($data['avatar_file'] ?? null);

            $sportBlock->fill([
                'name' => $data['name'],
                'about' => $data['about'] ?? '',
                'place' => $data['place'] ?? '',
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email'] ?? '',
                'website' => $data['website'] ?? '',
            ]);

            if ($avatar) {
                $sportBlock->avatar = $avatar;
            }

            $sportBlock->save();
            $this->syncGeoTarget($sportBlock, (int) ($data['city_id'] ?? 0));

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
