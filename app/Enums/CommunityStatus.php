<?php

namespace App\Enums;

enum CommunityStatus: int
{
    case New = 0;
    case Confirmed = 1;
    case Blocked = 2;
    case Hidden = 3;

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Confirmed => 'Confirmed',
            self::Blocked => 'Blocked',
            self::Hidden => 'Hidden',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::New => 'bg-success',
            self::Confirmed => '',
            self::Blocked => 'bg-danger',
            self::Hidden => 'bg-warning',
        };
    }

    public function isVisible(): bool
    {
        return in_array($this, [self::New, self::Confirmed], true);
    }

    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    public static function options(): array
    {
        return [
            self::New->value => self::New->label(),
            self::Confirmed->value => self::Confirmed->label(),
            self::Blocked->value => self::Blocked->label(),
            self::Hidden->value => self::Hidden->label(),
        ];
    }

    /**
     * Returns status values available on the frontend.
     *
     * @return array<int, int>
     */
    public static function visibleValues(): array
    {
        return [
            self::New->value,
            self::Confirmed->value,
        ];
    }
}
