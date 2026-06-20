<?php

namespace App\Service;

use App\Enums\UserStatus;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Sends a reset link when a matching account exists.
     */
    public function sendResetLink(string $email): void
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $email)
            ->where('status', '!=', UserStatus::Deleted->value)
            ->first();

        if (! $user) {
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ],
        );

        Mail::to($email)->send(new PasswordResetMail(
            $user,
            route('front.password.restore', ['token' => $token, 'email' => $email]),
        ));
    }

    /**
     * Updates the account password when the reset token is valid.
     */
    public function reset(string $email, string $token, string $password): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $row || ! hash_equals((string) $row->token, hash('sha256', $token))) {
            return false;
        }

        $createdAt = $row->created_at ? Carbon::parse($row->created_at) : null;
        if (! $createdAt || $createdAt->lt(now()->subMinutes(60))) {
            return false;
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $email)
            ->where('status', '!=', UserStatus::Deleted->value)
            ->first();

        if (! $user) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return true;
    }
}
