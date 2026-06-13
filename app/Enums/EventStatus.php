<?php

namespace App\Enums;

enum EventStatus: int
{
    case New = 0;
    case Confirmed = 1;
    case Blocked = 2;

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Confirmed => 'Подтвержденный',
            self::Blocked => 'Заблокирован',
        };
    }

    public function cssColor(): string
    {
        return match ($this) {
            self::New => 'bg-success',
            self::Confirmed => '',
            self::Blocked => 'bg-danger',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Confirmed;
    }

    /**
     * Возвращает подпись статуса мероприятия по числовому значению.
     */
    public static function labelFor(?int $status): string
    {
        return (self::tryFrom((int) $status) ?? self::New)->label();
    }

    /**
     * Возвращает CSS-класс статуса мероприятия по числовому значению.
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
        ];
    }
}
