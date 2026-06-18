<?php

namespace App\Service;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Carbon;

class UserOnlineStatusService
{
    private const ONLINE_TTL_MINUTES = 5;

    /**
     * Updates the last activity timestamp for the authenticated user.
     */
    public function touch(User $user): void
    {
        UserActivity::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['last_activity' => Carbon::now()]
        );
    }

    /**
     * Checks whether the user has been active during the online window.
     */
    public function isOnline(?User $user): bool
    {
        if (! $user?->activity?->last_activity) {
            return false;
        }

        return $user->activity->last_activity->greaterThan(Carbon::now()->subMinutes(self::ONLINE_TTL_MINUTES));
    }
}
