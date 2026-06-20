<?php

namespace App\Service;

use App\Enums\UserStatus;
use App\Mail\AccountConfirmationMail;
use App\Models\AccountConfirmationToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountRegistrationService
{
    /**
     * Creates a user account and sends an email confirmation link.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            /** @var User $user */
            $user = User::query()->create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'nickname' => $data['nickname'] ?? null,
                'sex' => $data['sex'],
                'birthday' => $data['birthday'] ?? null,
                'status' => UserStatus::New->value,
                'confirmed_at' => null,
            ]);

            $this->sendConfirmation($user);

            return $user;
        });
    }

    /**
     * Confirms a pending account by a single-use token.
     */
    public function confirm(string $token): ?User
    {
        $tokenHash = hash('sha256', $token);

        /** @var AccountConfirmationToken|null $confirmation */
        $confirmation = AccountConfirmationToken::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $confirmation) {
            return null;
        }

        return DB::transaction(function () use ($confirmation): User {
            /** @var User $user */
            $user = User::query()
                ->whereKey($confirmation->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $confirmation->forceFill(['used_at' => now()])->save();

            $user->forceFill([
                'status' => UserStatus::Confirmed->value,
                'confirmed_at' => now(),
            ])->save();

            return $user;
        });
    }

    /**
     * Replaces pending confirmation tokens and sends a fresh confirmation email.
     */
    private function sendConfirmation(User $user): void
    {
        AccountConfirmationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $token = Str::random(64);

        AccountConfirmationToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        Mail::to($user->email)->send(new AccountConfirmationMail(
            $user,
            route('front.registration.confirm', ['token' => $token]),
        ));
    }
}
