<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserRepository extends BaseRepository
{
    /**
     * Connects модель и зависимости, с которыми работает репозиторий.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Считает подтвержденных активных users.
     */
    public function countConfirmed(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return $this->model->newQuery()
            ->where('status', UserStatus::Confirmed->value)
            ->count();
    }

    /**
     * Finds user по email для authorization.
     *
     * @param string $email
     * @return User|null
     */
    public function findForLogin(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $user;
    }

    /**
     * Finds активную record по идентификатору.
     *
     * @param int $id
     * @return User|null
     */
    public function findActive(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->whereKey($id)
            ->where('status', UserStatus::Confirmed->value)
            ->first();

        return $user;
    }

    /**
     * Checks, что password user хранится в bcrypt-формате.
     */
    public function passwordUsesBcrypt(User $user): bool
    {
        return password_get_info((string) $user->password)['algoName'] === 'bcrypt';
    }
}
