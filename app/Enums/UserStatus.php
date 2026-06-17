<?php

namespace App\Enums;

enum UserStatus: int
{
    case New = 0;
    case Confirmed = 1;
    case Blocked = 2;
    case Deleted = 3;

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Confirmed => 'Confirmed',
            self::Blocked => 'Blocked',
            self::Deleted => 'Deleted',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::New => 'bg-success',
            self::Confirmed => '',
            self::Blocked => 'bg-danger',
            self::Deleted => 'bg-secondary',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Confirmed;
    }

    public function blocksLogin(): bool
    {
        return in_array($this, [self::Blocked, self::Deleted], true);
    }

    /**
     * Returns signature statusа user по numeric value.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Returns CSS-класс statusа user по numeric value.
     */
    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    /**
     * Returns options statusов для форм admin panel.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::New->value => self::New->label(),
            self::Confirmed->value => self::Confirmed->label(),
            self::Blocked->value => self::Blocked->label(),
            self::Deleted->value => self::Deleted->label(),
        ];
    }
}
