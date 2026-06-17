<?php

namespace App\Enums;

enum MembershipRole: int
{
    case Applied = 0;
    case Owner = 1;
    case Admin = 2;
    case Member = 3;
    case Blocked = 4;
    case Invited = 5;

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Administrator',
            self::Member => 'Member',
            self::Applied => 'Request',
            self::Blocked => 'Blocked',
            self::Invited => 'Invited',
        };
    }

    public function membershipType(): string
    {
        return match ($this) {
            self::Owner  => 'owner',
            self::Admin  => 'admin',
            self::Member => 'member',
            self::Applied => 'applied',
            self::Blocked => 'blocked',
            self::Invited => 'invited',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::Blocked  => 'text-danger',
            self::Applied  => 'text-info',
            self::Invited  => 'text-success',
        };
    }

    public static function labelFor(?int $role): string
    {
        if ($role === null) {
            return '';
        }

        return self::tryFrom($role)?->label() ?? '';
    }

    public static function membershipTypeFor(?int $role): string
    {
        if ($role === null) {
            return 'none';
        }

        return self::tryFrom($role)?->membershipType() ?? 'none';
    }
}
