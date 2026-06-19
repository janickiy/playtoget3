<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserRepository extends BaseRepository
{
    /**
     * Connects model and dependencies that the repository works with.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Counts confirmed active users.
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
     * Finds a user by email for authentication.
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
     * Finds active record by identifier.
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
     * Checks that user password is stored in bcrypt format.
     */
    public function passwordUsesBcrypt(User $user): bool
    {
        return password_get_info((string) $user->password)['algoName'] === 'bcrypt';
    }
}
