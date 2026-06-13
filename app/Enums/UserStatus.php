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
            self::New => 'Новый',
            self::Confirmed => 'Подтвержденный',
            self::Blocked => 'Заблокирован',
            self::Deleted => 'Удален',
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
     * Возвращает подпись статуса пользователя по числовому значению.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Возвращает CSS-класс статуса пользователя по числовому значению.
     */
    public static function cssColorFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->cssColor();
    }

    /**
     * Возвращает варианты статусов для форм админки.
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
