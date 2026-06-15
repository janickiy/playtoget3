<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class SocialAccountRepository extends BaseRepository
{
    public function __construct(
        UserSocialAccount $model,
        private readonly User $users,
    ) {
        parent::__construct($model);
    }

    /**
     * Находит пользователя по соцаккаунту или создает подтвержденную учетную запись.
     */
    public function findOrCreateUser(string $provider, SocialiteUser $profile): User
    {
        $providerUserId = (string) $profile->getId();

        if ($providerUserId === '') {
            throw new RuntimeException('Не удалось получить идентификатор пользователя.');
        }

        return DB::transaction(function () use ($provider, $profile, $providerUserId): User {
            /** @var UserSocialAccount|null $account */
            $account = $this->model->newQuery()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($account) {
                /** @var User $user */
                $user = $account->user()->firstOrFail();
                $this->ensureAllowedToLogin($user);
                $account->fill($this->accountPayload($profile, $providerUserId))->save();

                return $user;
            }

            $email = $this->emailFor($provider, $providerUserId, $profile);
            /** @var User|null $user */
            $user = $this->users->newQuery()->where('email', $email)->first();

            if ($user) {
                $this->ensureAllowedToLogin($user);
                $this->confirmUser($user);
            } else {
                $user = $this->createUser($provider, $email, $profile);
            }

            $this->model->newQuery()->create([
                'user_id' => $user->id,
                'provider' => $provider,
                ...$this->accountPayload($profile, $providerUserId),
            ]);

            return $user;
        });
    }

    /**
     * Возвращает ошибку авторизации для заблокированных и удаленных пользователей.
     */
    private function ensureAllowedToLogin(User $user): void
    {
        if ($user->isBlocked() || $user->isDeleted()) {
            throw new RuntimeException('Неверный email или пароль.');
        }
    }

    /**
     * Подтверждает существующего пользователя, если он пришел через проверенный OAuth-провайдер.
     */
    private function confirmUser(User $user): void
    {
        if ($user->isConfirmed()) {
            return;
        }

        $user->forceFill([
            'status' => UserStatus::Confirmed->value,
            'confirmed_at' => now(),
        ])->save();
    }

    /**
     * Создает нового пользователя на основе данных, полученных от OAuth-провайдера.
     */
    private function createUser(string $provider, string $email, SocialiteUser $profile): User
    {
        [$firstname, $lastname] = $this->nameParts($provider, $profile);

        /** @var User $user */
        $user = $this->users->newQuery()->create([
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'avatar' => $profile->getAvatar(),
            'status' => UserStatus::Confirmed->value,
            'confirmed_at' => now(),
        ]);

        return $user;
    }

    /**
     * Возвращает email провайдера или стабильный технический email для сервисов без email.
     */
    private function emailFor(string $provider, string $providerUserId, SocialiteUser $profile): string
    {
        $email = trim(Str::lower((string) $profile->getEmail()));

        if ($email !== '') {
            return $email;
        }

        return sprintf('%s_%s@social.local', $provider, substr(sha1($provider . '|' . $providerUserId), 0, 16));
    }

    /**
     * Готовит имя и фамилию для локального профиля пользователя.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function nameParts(string $provider, SocialiteUser $profile): array
    {
        $name = trim((string) ($profile->getName() ?: $profile->getNickname()));

        if ($name === '') {
            return [Str::headline($provider), null];
        }

        $parts = preg_split('/\s+/u', $name, 2) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    /**
     * Возвращает общие атрибуты привязки соцаккаунта.
     *
     * @return array<string, string|null>
     */
    private function accountPayload(SocialiteUser $profile, string $providerUserId): array
    {
        return [
            'provider_user_id' => $providerUserId,
            'email' => $profile->getEmail(),
            'name' => $profile->getName() ?: $profile->getNickname(),
            'avatar' => $profile->getAvatar(),
        ];
    }
}
