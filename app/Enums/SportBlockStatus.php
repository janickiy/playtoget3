<?php

namespace App\Enums;

enum SportBlockStatus: int
{
    case New = 0;
    case Confirmed = 1;
    case Blocked = 2;
    case Hidden = 3;

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Confirmed => 'Подтвержденный',
            self::Blocked => 'Заблокирован',
            self::Hidden => 'Скрытый',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::New => 'bg-success',
            self::Confirmed => '',
            self::Blocked => 'bg-danger',
            self::Hidden => 'bg-secondary',
        };
    }

    public function isVisible(): bool
    {
        return in_array($this, [self::New, self::Confirmed], true);
    }

    /**
     * Возвращает подпись статуса спортивного блока по числовому значению.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Возвращает CSS-класс статуса спортивного блока по числовому значению.
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
            self::Hidden->value => self::Hidden->label(),
        ];
    }

    /**
     * Возвращает значения статусов, доступных на фронте.
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
