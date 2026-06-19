<?php

namespace App\Service;

use App\DTO\Profile\ProfileSettingsData;
use App\Models\User;
use App\Repositories\ProfileRepository;

class ProfileUpdateService
{
    public function __construct(
        private readonly ProfileRepository $profiles,
    ) {
    }

    /**
     * Updates personal profile fields, contact settings, privacy, notifications and profile media.
     *
     * @throws \Throwable
     */
    public function update(User $user, ProfileSettingsData $data): void
    {
        $this->profiles->updatePersonalProfile($user, $data->profile);
        $this->profiles->updateProfileSettings($user, $data);
    }
}
