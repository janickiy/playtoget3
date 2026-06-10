<?php

namespace App\Enums;

enum CommunityPrivacyType: int
{
    case Open = 0;
    case Private = 1;
    case Closed = 2;

    public function label(string $kind = 'team'): string
    {
        $noun = $kind === 'group' ? 'группа' : 'команда';

        return match ($this) {
            self::Private => 'Приватная ' . $noun,
            self::Closed => 'Закрытая ' . $noun,
            self::Open => 'Открытая ' . $noun,
        };
    }

    public static function labelFor(int $type, string $kind = 'team'): string
    {
        return (self::tryFrom($type) ?? self::Open)->label($kind);
    }
}
