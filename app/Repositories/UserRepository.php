<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function countConfirmed(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return $this->model->newQuery()
            ->where('confirmed', true)
            ->where('banned', false)
            ->where('deleted', false)
            ->count();
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function findForLogin(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->where('email', $email)
            ->where('confirmed', true)
            ->where('banned', false)
            ->where('deleted', false)
            ->first();

        return $user;
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function findActive(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->whereKey($id)
            ->where('confirmed', true)
            ->where('banned', false)
            ->where('deleted', false)
            ->first();

        return $user;
    }

    public function passwordUsesBcrypt(User $user): bool
    {
        return password_get_info((string) $user->password)['algoName'] === 'bcrypt';
    }
}
