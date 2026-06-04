<?php

namespace App\Repositories;

use App\DTO\Admin\AdminData;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminRepository extends BaseRepository
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        return Admin::$role_name;
    }

    public function createFromData(AdminData $data): Builder|Model
    {
        $payload = $data->toArray();
        $payload['password'] = Hash::make((string) $data->password);

        return $this->create($payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createFromArray(array $data): Builder|Model
    {
        return $this->createFromData(AdminData::fromArray($data));
    }

    public function updateFromData(AdminData $data): bool
    {
        $payload = $data->toArray();

        if ($data->password !== null) {
            $payload['password'] = Hash::make($data->password);
        }

        return $this->update($data->id, $payload);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromArray(array $data): bool
    {
        return $this->updateFromData(AdminData::fromArray($data));
    }
}
