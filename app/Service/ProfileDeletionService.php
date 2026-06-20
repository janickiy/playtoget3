<?php

namespace App\Service;

use App\Enums\UserStatus;
use App\Mail\AccountDeletionConfirmationMail;
use App\Models\AccountDeleteToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProfileDeletionService
{
    /**
     * Creates a single-use account deletion token and sends the confirmation email.
     */
    public function sendConfirmation(User $user): void
    {
        if (! $user->email || $user->isDeleted()) {
            return;
        }

        AccountDeleteToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $token = Str::random(64);

        AccountDeleteToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        Mail::to($user->email)->send(new AccountDeletionConfirmationMail(
            $user,
            route('front.profile.delete-account.confirm', ['token' => $token]),
        ));
    }

    /**
     * Marks the account as deleted and removes active database sessions.
     */
    public function confirm(string $token): User
    {
        $tokenHash = hash('sha256', $token);

        /** @var AccountDeleteToken|null $deleteToken */
        $deleteToken = AccountDeleteToken::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        abort_if(! $deleteToken, 404);

        return DB::transaction(function () use ($deleteToken): User {
            /** @var User $user */
            $user = User::query()
                ->whereKey($deleteToken->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $deleteToken->forceFill(['used_at' => now()])->save();

            $user->forceFill([
                'status' => UserStatus::Deleted->value,
                'remember_token' => Str::random(60),
            ])->save();

            $this->deleteDatabaseSessions($user);

            return $user;
        });
    }

    /**
     * Deletes all database session records for the selected user.
     */
    private function deleteDatabaseSessions(User $user): void
    {
        $table = (string) config('session.table', 'sessions');

        if ($table !== '' && Schema::hasTable($table)) {
            DB::table($table)->where('user_id', $user->id)->delete();
        }
    }
}
