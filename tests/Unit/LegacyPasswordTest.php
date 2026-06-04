<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyPasswordTest extends TestCase
{
    public function test_legacy_md5_password_is_detected_before_bcrypt_check(): void
    {
        $repository = new UserRepository(new User());
        $user = new User(['password' => md5('1111')]);

        $this->assertTrue($repository->passwordMatchesLegacy($user, '1111'));
        $this->assertFalse($repository->passwordUsesBcrypt($user));
    }

    public function test_bcrypt_password_is_detected_as_laravel_hash(): void
    {
        $repository = new UserRepository(new User());
        $user = new User(['password' => Hash::make('1111')]);

        $this->assertFalse($repository->passwordMatchesLegacy($user, '1111'));
        $this->assertTrue($repository->passwordUsesBcrypt($user));
    }
}
