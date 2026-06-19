<?php

namespace App\Repositories;

use App\DTO\Admin\AdminData;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminRepository extends BaseRepository
{
    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    /**
     * Returns available administrator roles for form.
     *
     * @return array<string, string>
     */
    public function roleOptions(): array
    {
        return Admin::$role_name;
    }

    /**
     * Creates record from DTO with prepared data
     *
     * @param AdminData $data
     * @return Builder|Model
     */
    public function createFromData(AdminData $data): Builder|Model
    {
        $payload = $data->toArray();
        $payload['password'] = Hash::make((string) $data->password);

        return $this->create($payload);
    }

    /**
     * Creates a record from the data array, converting it to a DTO.
     *
     * @param array $data
     * @return Builder|Model
     */
    public function createFromArray(array $data): Builder|Model
    {
        return $this->createFromData(AdminData::fromArray($data));
    }

    /**
     * Updates record from DTO with prepared data.
     *
     * @param AdminData $data
     * @return bool
     */
    public function updateFromData(AdminData $data): bool
    {
        $payload = $data->toArray();

        if ($data->password !== null) {
            $payload['password'] = Hash::make($data->password);
        }

        return $this->update($data->id, $payload);
    }

    /**
     * Updates record from the data array, converting it to DTO.
     *
     * @param array<string, mixed> $data
     */
    public function updateFromArray(array $data): bool
    {
        return $this->updateFromData(AdminData::fromArray($data));
    }
}
